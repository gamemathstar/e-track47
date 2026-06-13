<?php

namespace App\Models;

use App\Models\FacilitatorSector;
use App\Services\V2\Notifications\NotificationDispatcher;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Notification extends Model
{
    use HasFactory;

    public static function submitTrackingForRewiew($tracking)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        
        $userRole = $user->role();
        $userSector = $user->sector();
        
        if (!$userRole || !$userSector || !$tracking->kpi) {
            return;
        }
        
        // Get all delivery unit users (Coordinators, Deputy Coordinators, and Facilitators)
        // For Facilitators, only notify those assigned to the relevant sector
        $deliveryUnitRoleIds = UserRole::whereIn('role', [
            'Coordinator',
            'Deputy Coordinator',
            'Facilitator',
            'Delivery Department' // For backward compatibility
        ])
            ->where('role_status', 'Active')
            ->get();
        
        // Filter Facilitators by sector if applicable
        $sectorId = $tracking->kpi->deliverable->commitment->sector_id ?? null;
        $receiverIds = [];
        foreach ($deliveryUnitRoleIds as $role) {
            if ($role->role === 'Facilitator' && $sectorId) {
                // Only include Facilitators assigned to this sector (check facilitator_sectors pivot table)
                $isAssignedToSector = FacilitatorSector::where('user_role_id', $role->id)
                    ->where('sector_id', $sectorId)
                    ->exists();
                if ($isAssignedToSector) {
                    $receiverIds[] = $role->user_id;
                }
            } else {
                // Coordinators and Deputy Coordinators (access all sectors)
                $receiverIds[] = $role->user_id;
            }
        }
        
        $receivers = User::whereIn('id', array_unique($receiverIds))->get();
        
        if ($receivers->isEmpty()) {
            return;
        }

        $body = $userRole->role . ' of ' . $userSector->sector_name . ' made a submission on ' . $tracking->kpi->kpi . '. It awaits your review';
        $forme = 'Your request on ' . $tracking->kpi->kpi . ' has been submitted to Delivery Unit. It is waiting for review';

        // Send notification to all relevant delivery unit users
        foreach ($receivers as $receiver) {
            self::make($user, $receiver, $tracking, 'Review Request', $body, 'Tracking Submitted');
            self::make($receiver, $user, $tracking, 'Tracking Submitted', $forme, 'System');
        }
    }

    public static function submitTrackingReview($tracking)
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }
        
        if (!$tracking->kpi || !$tracking->kpi->deliverable || !$tracking->kpi->deliverable->commitment || !$tracking->kpi->deliverable->commitment->sector) {
            return;
        }
        
        $receiverId = $tracking->kpi->deliverable->commitment->sector->sector_head_id;
        if (!$receiverId) {
            return;
        }
        
        $receiver = User::find($receiverId);
        if (!$receiver) {
            return;
        }
        
        $receiverRole = $receiver->role();
        $receiverSector = $receiver->sector();
        
        if (!$receiverRole || !$receiverSector) {
            return;
        }

        $body = 'Delivery Unit ' . $tracking->confirmation_status . ' your submission on '
            . $tracking->kpi->kpi . '.';
        $forme = 'Your review on ' . $tracking->kpi->kpi . ' has been submitted to ' . $receiverRole->role
            . ' of ' . $receiverSector->sector_name;

        self::make($user, $receiver, $tracking, 'Tracking Reviewed', $body, 'Tracking Reviewed');
        self::make($receiver, $user, $tracking, 'Tracking Reviewed', $forme, 'System');
    }

    // ------------------------------------------------------------------------
    // Approval-lifecycle helpers. All five (now six) delegate to
    // NotificationDispatcher so the inbox copy + push delivery + recipient
    // resolution match the v2 path exactly. KpiController call sites are
    // unchanged.
    //
    // Each helper:
    //   1. Resolves the next role-holder(s) via the dispatcher.
    //   2. Builds the unified copy via NotificationDispatcher::approvalCopy().
    //   3. Hands off to dispatch() which writes the inbox row + enqueues an
    //      FCM job per registered device token (gated by NotificationPreference
    //      and quiet hours).
    //
    // The pre-2024 self-confirmation rows ("Your submission has been sent to
    // …") were dropped — they don't fit the "notify the user who is currently
    // holding the role that's to act next" model: the actor already knows what
    // they just clicked.
    // ------------------------------------------------------------------------

    /**
     * Notify Sector Head when Data Admin submits performance tracking.
     */
    public static function notifySectorHeadForApproval($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_SUBMITTED,
            NotificationDispatcher::KIND_SUBMISSION,
            fn (NotificationDispatcher $d) => $d->sectorHeadsForTracking($tracking),
        );
    }

    /**
     * Notify Facilitator (assigned to the sector) when Sector Head approves.
     */
    public static function notifyFacilitatorAfterSectorHeadApproval($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_SECTOR_HEAD_ACCEPTED,
            NotificationDispatcher::KIND_APPROVAL,
            fn (NotificationDispatcher $d) => $d->facilitatorsForTracking($tracking),
        );
    }

    /**
     * Notify Coordinator(s) when Facilitator confirms.
     */
    public static function notifyCoordinatorAfterFacilitatorConfirmation($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_FACILITATOR_ACCEPTED,
            NotificationDispatcher::KIND_APPROVAL,
            fn (NotificationDispatcher $d) => $d->coordinators(),
        );
    }

    /**
     * Notify the Data Admin(s) of the sector when Coordinator finalises the
     * submission (terminal state — equivalent of v2's `coordinator_accepted`).
     * Previously absent from the web flow; added so the chain is symmetric
     * across web + v2 surfaces.
     */
    public static function notifyDataAdminAfterCoordinatorConfirmation($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_COORDINATOR_ACCEPTED,
            NotificationDispatcher::KIND_APPROVAL,
            fn (NotificationDispatcher $d) => $d->dataAdminsForTracking($tracking),
        );
    }

    /**
     * Notify Data Admin(s) when Facilitator rejects.
     */
    public static function notifyDataAdminAfterFacilitatorRejection($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_REJECTED,
            NotificationDispatcher::KIND_REJECTION,
            fn (NotificationDispatcher $d) => $d->dataAdminsForTracking($tracking),
            [
                'rejectingRole' => 'Facilitator',
                'rejectionReason' => $tracking->facilitator_rejection_reason ?? null,
            ],
        );
    }

    /**
     * Notify Data Admin(s) when Coordinator rejects after facilitator acceptance.
     */
    public static function notifyDataAdminAfterCoordinatorRejection($tracking): void
    {
        self::dispatchApprovalLifecycle(
            $tracking,
            NotificationDispatcher::STAGE_REJECTED,
            NotificationDispatcher::KIND_REJECTION,
            fn (NotificationDispatcher $d) => $d->dataAdminsForTracking($tracking),
            [
                'rejectingRole' => 'Coordinator',
                'rejectionReason' => $tracking->coordinator_rejection_reason ?? null,
            ],
        );
    }

    // ------------------------------------------------------------------------
    // Data-entry window helpers (§11.7). Web entry points for the same fan-out
    // the v2 DataEntryWindowService does — same recipients, same copy, same
    // dispatch path.
    // ------------------------------------------------------------------------

    /**
     * Notify the sector's involved roles (Data Admin + Sector Head +
     * Facilitator(s)) that the data-entry window has been opened. Pass the
     * DataEntryAccess row that was just transitioned to `open`.
     */
    public static function notifySectorParticipantsOnWindowOpen($access): void
    {
        self::dispatchWindowChange($access, NotificationDispatcher::STAGE_WINDOW_OPENED);
    }

    /**
     * Notify the sector's involved roles that an override has been granted.
     * Pass the DataEntryAccess row carrying the override metadata (reason +
     * optional deadline).
     */
    public static function notifySectorParticipantsOnOverrideGranted($access): void
    {
        self::dispatchWindowChange($access, NotificationDispatcher::STAGE_WINDOW_OVERRIDE_GRANTED, [
            'reason' => $access->override_reason ?? null,
            'expiresAt' => $access->override_deadline
                ? \Illuminate\Support\Carbon::parse($access->override_deadline)->toIso8601String()
                : null,
        ]);
    }

    /**
     * Notify the sector's involved roles that the data-entry window has been
     * closed. Pass the DataEntryAccess row that was just transitioned to
     * `closed`. Used by lock/lockAll.
     */
    public static function notifySectorParticipantsOnWindowLock($access): void
    {
        self::dispatchWindowChange($access, NotificationDispatcher::STAGE_WINDOW_LOCKED);
    }

    /**
     * Shared dispatch path for window-state changes (called by the two helpers
     * above). Resolves participants via the dispatcher's sectorParticipantsFor,
     * builds copy via NotificationDispatcher::windowCopy().
     */
    private static function dispatchWindowChange($access, string $stage, array $extraCtx = []): void
    {
        \Illuminate\Support\Facades\Log::info('notification.attempt', [
            'source' => 'web.window.'.$stage,
            'stage' => $stage,
            'access_id' => (int) ($access->id ?? 0),
            'sector_id' => (int) ($access->sector_id ?? 0),
        ]);

        if (! $access || ! $access->sector_id) {
            \Illuminate\Support\Facades\Log::warning('notification.attempt aborted', [
                'source' => 'web.window.'.$stage,
                'reason' => 'missing_access_or_sector',
            ]);
            return;
        }

        $dispatcher = app(NotificationDispatcher::class);
        $recipients = $dispatcher->sectorParticipantsFor((int) $access->sector_id);

        $sector = $access->sector ?? \App\Models\Sector::find($access->sector_id);
        $sectorName = (string) ($sector?->sector_name ?: 'a sector');

        [$title, $body] = NotificationDispatcher::windowCopy($stage, array_merge([
            'sectorName' => $sectorName,
            'quarter' => (int) ($access->quarter ?? 0),
            'year' => (int) ($access->year ?? 0),
        ], $extraCtx));

        $actor = Auth::user();
        $dispatcher->dispatch(
            $recipients,
            NotificationDispatcher::KIND_DEADLINE,
            $title,
            $body,
            [
                'senderId' => (int) ($actor?->id ?? 0),
                'modelId' => (int) $access->id,
                'deepLinkRoute' => 'dataEntryWindow',
                'deepLinkParams' => [
                    'sectorId' => (string) $access->sector_id,
                    'year' => (string) (int) ($access->year ?? 0),
                    'quarter' => 'q'.((int) ($access->quarter ?? 0)),
                ],
            ],
        );
    }

    /**
     * Shared plumbing for every approval-lifecycle notify helper. Resolves
     * recipients via the supplied closure, builds the unified copy from
     * NotificationDispatcher::approvalCopy(), and hands off to dispatch().
     *
     * @param  callable(NotificationDispatcher): iterable<User>  $recipientResolver
     * @param  array{rejectingRole?:string,rejectionReason?:?string}  $extraCopyCtx
     */
    private static function dispatchApprovalLifecycle($tracking, string $stage, string $kind, callable $recipientResolver, array $extraCopyCtx = []): void
    {
        \Illuminate\Support\Facades\Log::info('notification.attempt', [
            'source' => 'web.approval.'.$stage,
            'stage' => $stage,
            'kind' => $kind,
            'tracking_id' => (int) ($tracking->id ?? 0),
            'kpi_id' => (int) ($tracking->kpi->id ?? 0),
        ]);

        if (! $tracking->kpi || ! $tracking->kpi->deliverable || ! $tracking->kpi->deliverable->commitment) {
            \Illuminate\Support\Facades\Log::warning('notification.attempt aborted', [
                'source' => 'web.approval.'.$stage,
                'reason' => 'kpi_or_deliverable_or_commitment_missing',
                'tracking_id' => (int) ($tracking->id ?? 0),
            ]);
            return;
        }
        $sector = $tracking->kpi->deliverable->commitment->sector;
        if (! $sector) {
            \Illuminate\Support\Facades\Log::warning('notification.attempt aborted', [
                'source' => 'web.approval.'.$stage,
                'reason' => 'sector_missing',
                'tracking_id' => (int) ($tracking->id ?? 0),
            ]);
            return;
        }

        $dispatcher = app(NotificationDispatcher::class);
        $recipients = $recipientResolver($dispatcher);

        $copyCtx = array_merge([
            'kpiTitle' => (string) ($tracking->kpi->kpi ?: 'a KPI'),
            'sectorName' => (string) ($sector->sector_name ?: 'a sector'),
        ], $extraCopyCtx);

        [$title, $body] = NotificationDispatcher::approvalCopy($stage, $copyCtx);

        $actor = Auth::user();
        $dispatcher->dispatch(
            $recipients,
            $kind,
            $title,
            $body,
            [
                'senderId' => (int) ($actor?->id ?? 0),
                'modelId' => (int) $tracking->id,
                'deepLinkRoute' => NotificationDispatcher::approvalDeepLinkRoute($stage),
                'deepLinkParams' => ['kpiId' => (string) ($tracking->kpi->id ?? '')],
            ],
        );
    }

    public static function make(User $sender, User $recipient, Model $model, $title, $body, $type, $do = 1)
    {
        $notification = new Notification();
        $notification->user_id = $recipient->id;
        $notification->sender_id = $sender ? $sender->id : 0;
        $notification->title = $title;
        $notification->body = $body;
        $notification->type = $type;
        $notification->model_id = $model->id;
        $notification->status = 'Not Read';
        $notification->save();

        if ($do) {
            self::sendPushNotification($recipient, $title, $body);
        }

    }


    /**
     * Legacy FCM push via the pre-2024 "Legacy HTTP API". The endpoint
     * (fcm.googleapis.com/fcm/send) was shut down by Google on 20 June 2024
     * and silently returns errors — kept here so existing web call sites
     * (KpiController, DeliverableController, …) don't break, but no push
     * actually leaves the wire. New code should use the v2
     * NotificationDispatcher (HTTP v1 API).
     *
     * The legacy server key is read from FCM_LEGACY_SERVER_KEY in .env so it
     * isn't a committed credential. Returns false (no network call) when the
     * env var is not set.
     */
    public static function sendPushNotification($recipient, $title, $body)
    {
        $serverKey = config('services.fcm.legacy_server_key');
        if (! $serverKey) {
            return false;
        }

        if (is_array($recipient)) {
            $firebaseToken = User::whereIn('id', $recipient)->pluck('fcm_token')->all();
        } else {
            $firebaseToken = User::where('id', $recipient->id)->pluck('fcm_token')->all();
        }

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        return ($response);
    }
}
