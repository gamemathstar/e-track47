<?php

namespace App\Services\V2\Notifications;

use App\Jobs\SendFcmJob;
use App\Models\DeviceToken;
use App\Models\FacilitatorSector;
use App\Models\Notification;
use App\Models\NotificationPreference;
use App\Models\PerformanceTracking;
use App\Models\User;
use App\Models\UserRole;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Single entry point for v2 lifecycle notifications.
 *
 * Responsibilities per recipient:
 *  1. Write an in-app inbox row (`notifications` table) — always, regardless
 *     of push prefs. The inbox is the user's source of truth.
 *  2. Check NotificationPreference for the kind + the `push` channel + quiet
 *     hours. If push is allowed, enqueue one SendFcmJob per registered device
 *     token (multi-device fan-out). During quiet hours the job is delayed
 *     until the quiet window ends in the user's local clock.
 *
 * Recipient resolution lives here so callers (ApprovalService, KpiTracking,
 * Discussions, etc.) just hand over the tracking row + a kind and stay
 * decoupled from the role/sector model.
 */
class NotificationDispatcher
{
    /** Wire kinds the inbox understands. */
    public const KIND_SUBMISSION = 'submission';
    public const KIND_APPROVAL = 'approval';
    public const KIND_REJECTION = 'rejection';
    public const KIND_DISCUSSION = 'discussion';
    public const KIND_DEADLINE = 'deadline';
    public const KIND_MENTION = 'mention';
    public const KIND_SYSTEM = 'system';

    /** Approval-lifecycle stages (used by approvalCopy()). */
    public const STAGE_SUBMITTED = 'submitted';
    public const STAGE_SECTOR_HEAD_ACCEPTED = 'sector_head_accepted';
    public const STAGE_FACILITATOR_ACCEPTED = 'facilitator_accepted';
    public const STAGE_COORDINATOR_ACCEPTED = 'coordinator_accepted';
    public const STAGE_REJECTED = 'rejected';

    /** Data-entry window stages (used by windowCopy()). */
    public const STAGE_WINDOW_OPENED = 'window_opened';
    public const STAGE_WINDOW_OVERRIDE_GRANTED = 'window_override_granted';
    public const STAGE_WINDOW_LOCKED = 'window_locked';

    /**
     * Single source of truth for inbox + push copy on the approval lifecycle.
     * Both the v2 service path and the web's legacy Notification::* helpers
     * call this so the same wording lands in the inbox regardless of which
     * surface triggered the transition.
     *
     * Stages keep their copy intentionally short and parallel — title is a
     * 3–5-word imperative, body is "{Actor} {verb} \"{kpi}\" ({sector}).
     * {Next action}." A reject also gets the (trimmed) reason inline.
     *
     * @param  array{kpiTitle?:string,sectorName?:string,rejectingRole?:string,rejectionReason?:?string}  $ctx
     * @return array{0:string,1:string} [title, body]
     */
    public static function approvalCopy(string $stage, array $ctx = []): array
    {
        $kpi = (string) ($ctx['kpiTitle'] ?? 'KPI');
        $sector = (string) ($ctx['sectorName'] ?? 'sector');

        return match ($stage) {
            self::STAGE_SUBMITTED => [
                'Submission awaiting your approval',
                "Data Admin submitted \"{$kpi}\" ({$sector}). Review and approve.",
            ],
            self::STAGE_SECTOR_HEAD_ACCEPTED => [
                'Submission awaiting your verification',
                "Sector Head approved \"{$kpi}\" ({$sector}). Verify and confirm.",
            ],
            self::STAGE_FACILITATOR_ACCEPTED => [
                'Submission awaiting final approval',
                "Facilitator verified \"{$kpi}\" ({$sector}). Approve to finalise.",
            ],
            self::STAGE_COORDINATOR_ACCEPTED => [
                'Submission confirmed',
                "Coordinator finalised \"{$kpi}\" ({$sector}).",
            ],
            self::STAGE_REJECTED => (function () use ($kpi, $sector, $ctx) {
                $role = (string) ($ctx['rejectingRole'] ?? 'Reviewer');
                $reason = trim((string) ($ctx['rejectionReason'] ?? ''));
                if ($reason !== '' && mb_strlen($reason) > 160) {
                    $reason = mb_substr($reason, 0, 157).'…';
                }
                $reasonPart = $reason !== '' ? ": {$reason}" : '';
                return [
                    'Submission needs revision',
                    "{$role} rejected \"{$kpi}\" ({$sector}){$reasonPart}. Review and resubmit.",
                ];
            })(),
            default => ['Notification', 'You have a new update.'],
        };
    }

    /**
     * Inbox + push copy for data-entry window changes (§11.7). Sent to all
     * involved roles in the sector when the window opens or an override is
     * granted — i.e. the moments where someone can now act on data entry.
     *
     * @param  array{sectorName?:string,quarter?:int,year?:int,reason?:?string,expiresAt?:?string}  $ctx
     * @return array{0:string,1:string} [title, body]
     */
    public static function windowCopy(string $stage, array $ctx = []): array
    {
        $sector = (string) ($ctx['sectorName'] ?? 'the sector');
        $quarter = (int) ($ctx['quarter'] ?? 0);
        $year = (int) ($ctx['year'] ?? 0);
        $period = $quarter && $year ? "Q{$quarter} {$year}" : ($quarter ? "Q{$quarter}" : ($year ? (string) $year : 'this period'));

        return match ($stage) {
            self::STAGE_WINDOW_OPENED => [
                'Data entry window opened',
                "Data entry is now open for {$sector} ({$period}). Submit your performance values before the window closes.",
            ],
            self::STAGE_WINDOW_OVERRIDE_GRANTED => (function () use ($sector, $period, $ctx) {
                $expires = ! empty($ctx['expiresAt'])
                    ? ' until '.\Carbon\Carbon::parse((string) $ctx['expiresAt'])->format('j M Y')
                    : '';
                $reason = trim((string) ($ctx['reason'] ?? ''));
                if ($reason !== '' && mb_strlen($reason) > 140) {
                    $reason = mb_substr($reason, 0, 137).'…';
                }
                $reasonPart = $reason !== '' ? " Reason: {$reason}." : '';
                return [
                    'Data entry override granted',
                    "Data entry for {$sector} ({$period}) is open via override{$expires}.{$reasonPart} Submit your values now.",
                ];
            })(),
            self::STAGE_WINDOW_LOCKED => [
                'Data entry window closed',
                "Data entry is now closed for {$sector} ({$period}). The submission window has ended.",
            ],
            default => ['Notification', 'You have a new update.'],
        };
    }

    /**
     * Fan one notification out to a set of recipients.
     *
     * @param  iterable<User>  $recipients
     * @param  array{deepLinkRoute?:string,deepLinkParams?:array,modelId?:int,senderId?:int}  $context
     */
    public function dispatch(iterable $recipients, string $kind, string $title, string $body, array $context = []): void
    {
        $senderId = (int) ($context['senderId'] ?? 0);
        $modelId = (int) ($context['modelId'] ?? 0);
        $deepRoute = $context['deepLinkRoute'] ?? null;
        $deepParams = $context['deepLinkParams'] ?? null;
        $hasKind = Schema::hasColumn('notifications', 'kind');
        $hasDeepLinkRoute = Schema::hasColumn('notifications', 'deep_link_route');
        $hasDeepLinkParams = Schema::hasColumn('notifications', 'deep_link_params');

        // Entry log — every call to dispatch produces this line, so we can
        // tell whether the upstream caller actually invoked us. If you see no
        // `notification.dispatch attempt` for an event, the issue is upstream
        // of this method (caller early-returned, or wasn't called at all).
        $recipientCount = 0;
        foreach ($recipients as $recipient) {
            $recipientCount++;
            try {
                $this->dispatchOne($recipient, $kind, $title, $body, [
                    'senderId' => $senderId,
                    'modelId' => $modelId,
                    'deepLinkRoute' => $deepRoute,
                    'deepLinkParams' => $deepParams,
                    'hasKind' => $hasKind,
                    'hasDeepLinkRoute' => $hasDeepLinkRoute,
                    'hasDeepLinkParams' => $hasDeepLinkParams,
                ]);
            } catch (Throwable $e) {
                // Never let a notification failure surface as a 5xx on the
                // originating mutation. Just log + continue with the rest.
                report($e);
            }
        }

        Log::info('notification.dispatch attempt', [
            'kind' => $kind,
            'recipient_count' => $recipientCount,
            'model_id' => $modelId,
        ]);
        if ($recipientCount === 0) {
            Log::warning('notification.dispatch no_recipients', [
                'kind' => $kind,
                'model_id' => $modelId,
                'hint' => 'caller resolved an empty recipient set — verify role assignments',
            ]);
        }
    }

    private function dispatchOne(User $recipient, string $kind, string $title, string $body, array $ctx): void
    {
        $n = new Notification();
        $n->user_id = $recipient->id;
        $n->sender_id = $ctx['senderId'];
        $n->title = $title;
        $n->body = $body;
        $n->type = $kind; // legacy column — kindOf() reads from `kind` first then falls back to `type`
        $n->model_id = $ctx['modelId'];
        $n->status = 'Not Read';
        if ($ctx['hasKind']) {
            $n->kind = $kind;
        }
        if ($ctx['hasDeepLinkRoute'] && $ctx['deepLinkRoute']) {
            $n->deep_link_route = $ctx['deepLinkRoute'];
        }
        if ($ctx['hasDeepLinkParams'] && is_array($ctx['deepLinkParams']) && $ctx['deepLinkParams']) {
            $n->deep_link_params = json_encode($ctx['deepLinkParams']);
        }
        $n->save();

        // Trace block — every push decision now emits a single structured
        // log line so prod operators can grep `notification.push` and see
        // exactly why a given recipient did / didn't get pushed.
        // grep -i "notification.push" storage/logs/laravel.log
        $logCtx = [
            'recipient_id' => (int) $recipient->id,
            'kind' => $kind,
            'notification_id' => (int) $n->id,
        ];

        $skipReason = $this->pushSkipReason($recipient, $kind);
        if ($skipReason !== null) {
            Log::info('notification.push skipped', $logCtx + ['reason' => $skipReason]);
            return;
        }

        $tokens = $this->deviceTokensFor($recipient);
        if ($tokens->isEmpty()) {
            Log::info('notification.push skipped', $logCtx + ['reason' => 'no_device_tokens']);
            return;
        }

        $delay = $this->quietHoursDelay($recipient);

        foreach ($tokens as $deviceToken) {
            $job = SendFcmJob::dispatch(
                (int) $deviceToken->id,
                (string) $deviceToken->token,
                $title,
                $body,
                $this->fcmDataPayload($n, $kind, $ctx),
            );
            if ($delay) {
                $job->delay($delay);
            }
        }

        Log::info('notification.push enqueued', $logCtx + [
            'device_count' => $tokens->count(),
            'delayed_until' => $delay ? $delay->toIso8601String() : null,
        ]);
    }

    /**
     * Returns null if the push is allowed, or a short-string reason code
     * explaining why it's being skipped. Reasons:
     *   - `pref_push_off` — user disabled the push channel globally
     *   - `pref_kind_off` — user disabled this notification kind
     */
    private function pushSkipReason(User $recipient, string $kind): ?string
    {
        $prefs = NotificationPreference::where('user_id', $recipient->id)->first();
        if (! $prefs) {
            return null;
        }
        if (! $prefs->push) {
            return 'pref_push_off';
        }
        $kindAllowed = match ($kind) {
            self::KIND_SUBMISSION => (bool) $prefs->submissions,
            self::KIND_APPROVAL => (bool) $prefs->approvals,
            self::KIND_REJECTION => (bool) $prefs->rejections,
            self::KIND_DISCUSSION, self::KIND_MENTION => (bool) ($kind === self::KIND_MENTION ? $prefs->mentions : true),
            self::KIND_DEADLINE => (bool) $prefs->deadlines,
            default => true, // system: never gated
        };
        return $kindAllowed ? null : 'pref_kind_off';
    }

    /**
     * If quiet hours are active for the recipient now, return the Carbon
     * timestamp the job should fire at (server tz). Null means "send now".
     */
    private function quietHoursDelay(User $recipient): ?Carbon
    {
        $prefs = NotificationPreference::where('user_id', $recipient->id)->first();
        if (! $prefs || ! $prefs->quiet_hours_enabled) {
            return null;
        }

        $now = Carbon::now();
        $start = $now->copy()->setTime((int) $prefs->quiet_from_hour, (int) $prefs->quiet_from_minute, 0);
        $end = $now->copy()->setTime((int) $prefs->quiet_to_hour, (int) $prefs->quiet_to_minute, 0);

        // Wrap past midnight (e.g. quiet 22:00 → 06:00).
        if ($end->lessThanOrEqualTo($start)) {
            $end->addDay();
            if ($now->lessThan($start)) {
                $start->subDay();
                $end->subDay();
            }
        }

        if ($now->between($start, $end)) {
            return $end;
        }
        return null;
    }

    /** @return Collection<int, DeviceToken> */
    private function deviceTokensFor(User $recipient): Collection
    {
        return DeviceToken::where('user_id', $recipient->id)->get();
    }

    private function fcmDataPayload(Notification $n, string $kind, array $ctx): array
    {
        $data = [
            'notificationId' => (string) $n->id,
            'kind' => $kind,
        ];
        if ($ctx['deepLinkRoute']) {
            $data['deepLinkRoute'] = (string) $ctx['deepLinkRoute'];
        }
        if (is_array($ctx['deepLinkParams']) && $ctx['deepLinkParams']) {
            $data['deepLinkParams'] = $ctx['deepLinkParams']; // KreaitFcmTransport JSON-encodes nested
        }
        return $data;
    }

    // --- recipient helpers (used by feature services) -----------------------

    /**
     * Active Sector Heads of the sector the given PerformanceTracking row
     * belongs to. May be empty if no Sector Head has been assigned.
     *
     * @return Collection<int, User>
     */
    public function sectorHeadsForTracking(PerformanceTracking $tracking): Collection
    {
        $sectorId = optional(optional(optional($tracking->kpi)->deliverable)->commitment)->sector_id;
        if (! $sectorId) {
            return new Collection();
        }
        $userIds = UserRole::where('role', UserRole::ROLE_SECTOR_HEAD)
            ->where('entity_id', $sectorId)
            ->where('role_status', 'Active')
            ->pluck('user_id')->all();
        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Active Facilitators assigned (via facilitator_sectors) to the sector the
     * given tracking row belongs to.
     *
     * @return Collection<int, User>
     */
    public function facilitatorsForTracking(PerformanceTracking $tracking): Collection
    {
        $sectorId = optional(optional(optional($tracking->kpi)->deliverable)->commitment)->sector_id;
        if (! $sectorId) {
            return new Collection();
        }
        $roleIds = FacilitatorSector::where('sector_id', $sectorId)
            ->whereHas('userRole', fn ($q) => $q
                ->where('role', UserRole::ROLE_FACILITATOR)
                ->where('role_status', 'Active'))
            ->pluck('user_role_id')->all();
        $userIds = UserRole::whereIn('id', $roleIds)->pluck('user_id')->all();
        return User::whereIn('id', $userIds)->get();
    }

    /**
     * Active Coordinators + Deputy Coordinators (state-wide access).
     *
     * @return Collection<int, User>
     */
    public function coordinators(): Collection
    {
        $userIds = UserRole::whereIn('role', [UserRole::ROLE_COORDINATOR, UserRole::ROLE_DEPUTY_COORDINATOR])
            ->where('role_status', 'Active')
            ->pluck('user_id')->all();
        return User::whereIn('id', $userIds)->get();
    }

    /**
     * The Data Admin(s) of the sector the given tracking row belongs to. The
     * **original submitter** is the natural target for accept/reject feedback,
     * but we fan out to all active Data Admins of the sector so any of them
     * can act on it.
     *
     * @return Collection<int, User>
     */
    public function dataAdminsForTracking(PerformanceTracking $tracking): Collection
    {
        $sectorId = optional(optional(optional($tracking->kpi)->deliverable)->commitment)->sector_id;
        if (! $sectorId) {
            return new Collection();
        }
        $userIds = UserRole::where('role', UserRole::ROLE_DATA_ADMIN)
            ->where('entity_id', $sectorId)
            ->where('role_status', 'Active')
            ->pluck('user_id')->all();
        // Include the submitter explicitly in case their role row drifted.
        if ($tracking->submitted_by) {
            $userIds[] = (int) $tracking->submitted_by;
        }
        return User::whereIn('id', array_unique($userIds))->get();
    }

    /**
     * All "involved" users for a sector — Data Admin(s) + Sector Head +
     * Facilitator(s) assigned to it. Coordinators are excluded; they are the
     * actors who trigger window changes, not recipients of them.
     *
     * Each user appears at most once even if they wear two role hats for the
     * same sector (rare but happens with deprecated/legacy rows).
     *
     * @return Collection<int, User>
     */
    public function sectorParticipantsFor(int|string $sectorId): Collection
    {
        $sectorId = (int) $sectorId;
        if ($sectorId <= 0) {
            return new Collection();
        }

        // Data Admins + Sector Head: direct entity_id match on user_roles.
        $directIds = UserRole::whereIn('role', [UserRole::ROLE_DATA_ADMIN, UserRole::ROLE_SECTOR_HEAD])
            ->where('entity_id', $sectorId)
            ->where('role_status', 'Active')
            ->pluck('user_id')->all();

        // Facilitators: indirect via facilitator_sectors pivot.
        $facRoleIds = FacilitatorSector::where('sector_id', $sectorId)
            ->whereHas('userRole', fn ($q) => $q
                ->where('role', UserRole::ROLE_FACILITATOR)
                ->where('role_status', 'Active'))
            ->pluck('user_role_id')->all();
        $facIds = UserRole::whereIn('id', $facRoleIds)->pluck('user_id')->all();

        $allIds = array_values(array_unique(array_merge($directIds, $facIds)));
        return User::whereIn('id', $allIds)->get();
    }
}
