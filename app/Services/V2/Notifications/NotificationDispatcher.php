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

        foreach ($recipients as $recipient) {
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

        if (! $this->pushAllowed($recipient, $kind)) {
            return;
        }

        $delay = $this->quietHoursDelay($recipient);
        $tokens = $this->deviceTokensFor($recipient);

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
    }

    /**
     * Whether the recipient's NotificationPreference allows a push for this
     * kind. Defaults to **allow** when no preference row exists yet.
     */
    private function pushAllowed(User $recipient, string $kind): bool
    {
        $prefs = NotificationPreference::where('user_id', $recipient->id)->first();
        if (! $prefs) {
            return true;
        }
        if (! $prefs->push) {
            return false;
        }
        return match ($kind) {
            self::KIND_SUBMISSION => (bool) $prefs->submissions,
            self::KIND_APPROVAL => (bool) $prefs->approvals,
            self::KIND_REJECTION => (bool) $prefs->rejections,
            self::KIND_DISCUSSION, self::KIND_MENTION => (bool) ($kind === self::KIND_MENTION ? $prefs->mentions : true),
            self::KIND_DEADLINE => (bool) $prefs->deadlines,
            default => true, // system: never gated
        };
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
}
