<?php

namespace App\Models;

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
                // Only include Facilitators assigned to this sector
                if ($role->entity_id == $sectorId) {
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

    /**
     * Notify Sector Head when Data Admin submits performance tracking
     */
    public static function notifySectorHeadForApproval($tracking)
    {
        if (!$tracking->kpi || !$tracking->kpi->deliverable || !$tracking->kpi->deliverable->commitment || !$tracking->kpi->deliverable->commitment->sector) {
            return;
        }
        
        $sector = $tracking->kpi->deliverable->commitment->sector;
        $sectorHeadId = $sector->sector_head_id ?? null;
        
        if (!$sectorHeadId) {
            // Find Sector Head by role
            $sectorHeadRole = UserRole::where('role', UserRole::ROLE_SECTOR_HEAD)
                ->where('entity_id', $sector->id)
                ->where('role_status', UserRole::STATUS_ACTIVE)
                ->first();
            
            if ($sectorHeadRole) {
                $sectorHeadId = $sectorHeadRole->user_id;
            }
        }
        
        if (!$sectorHeadId) {
            return;
        }
        
        $sectorHead = User::find($sectorHeadId);
        $dataAdmin = Auth::user();
        
        if (!$sectorHead || !$dataAdmin) {
            return;
        }

        $body = 'Data Admin of ' . $sector->sector_name . ' has submitted performance tracking for ' . $tracking->kpi->kpi . '. Please review and approve.';
        $forme = 'Your performance tracking submission for ' . $tracking->kpi->kpi . ' has been sent to Sector Head for approval.';

        self::make($dataAdmin, $sectorHead, $tracking, 'Approval Required', $body, 'Tracking Submitted');
        self::make($sectorHead, $dataAdmin, $tracking, 'Submission Sent', $forme, 'System');
    }

    /**
     * Notify Facilitator when Sector Head approves data
     */
    public static function notifyFacilitatorAfterSectorHeadApproval($tracking)
    {
        if (!$tracking->kpi || !$tracking->kpi->deliverable || !$tracking->kpi->deliverable->commitment) {
            return;
        }
        
        $sectorId = $tracking->kpi->deliverable->commitment->sector_id;
        $sector = \App\Models\Sector::find($sectorId);
        
        if (!$sector) {
            return;
        }
        
        // Get Facilitators assigned to this sector
        $facilitatorRoles = UserRole::where('role', UserRole::ROLE_FACILITATOR)
            ->where('entity_id', $sectorId)
            ->where('role_status', UserRole::STATUS_ACTIVE)
            ->get();
        
        if ($facilitatorRoles->isEmpty()) {
            return;
        }
        
        $facilitatorIds = $facilitatorRoles->pluck('user_id')->toArray();
        $facilitators = User::whereIn('id', $facilitatorIds)->get();
        $sectorHead = Auth::user();
        
        if ($facilitators->isEmpty() || !$sectorHead) {
            return;
        }

        $body = 'Sector Head of ' . $sector->sector_name . ' has approved performance tracking for ' . $tracking->kpi->kpi . '. Please review and confirm.';
        $forme = 'Your approval for ' . $tracking->kpi->kpi . ' has been sent to Facilitator for confirmation.';

        foreach ($facilitators as $facilitator) {
            self::make($sectorHead, $facilitator, $tracking, 'Confirmation Required', $body, 'Tracking Approved');
        }
        self::make($sectorHead, $sectorHead, $tracking, 'Approval Sent', $forme, 'System');
    }

    /**
     * Notify Coordinator when Facilitator confirms
     */
    public static function notifyCoordinatorAfterFacilitatorConfirmation($tracking)
    {
        // Get all active Coordinators
        $coordinatorRoles = UserRole::whereIn('role', [UserRole::ROLE_COORDINATOR, UserRole::ROLE_DEPUTY_COORDINATOR])
            ->where('role_status', UserRole::STATUS_ACTIVE)
            ->where('entity_id', 0) // All sectors access
            ->get();
        
        if ($coordinatorRoles->isEmpty()) {
            return;
        }
        
        $coordinatorIds = $coordinatorRoles->pluck('user_id')->toArray();
        $coordinators = User::whereIn('id', $coordinatorIds)->get();
        $facilitator = Auth::user();
        
        if ($coordinators->isEmpty() || !$facilitator || !$tracking->kpi || !$tracking->kpi->deliverable || !$tracking->kpi->deliverable->commitment) {
            return;
        }
        
        $sector = $tracking->kpi->deliverable->commitment->sector;
        $sectorName = $sector ? $sector->sector_name : 'Unknown Sector';

        $body = 'Facilitator has confirmed performance tracking for ' . $tracking->kpi->kpi . ' from ' . $sectorName . '. Please review and provide final approval.';
        $forme = 'Your confirmation for ' . $tracking->kpi->kpi . ' has been sent to Coordinator for final approval.';

        foreach ($coordinators as $coordinator) {
            self::make($facilitator, $coordinator, $tracking, 'Final Approval Required', $body, 'Tracking Confirmed');
        }
        self::make($facilitator, $facilitator, $tracking, 'Confirmation Sent', $forme, 'System');
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


    public static function sendPushNotification($recipient, $title, $body)
    {
        if (is_array($recipient)) {
            $firebaseToken = User::whereIn('id', $recipient)->pluck('fcm_token')->all();
        } else {
            $firebaseToken = User::where('id', $recipient->id)->pluck('fcm_token')->all();
        }

        $SERVER_API_KEY = 'AAAA6lmBYck:APA91bHvFS-Ay68e0J1t8nDYGFdXGoDSGh0D6a2CFtp-hLZzefy1i1yui4pLCdMKCYhiYDaC_5-0H7tz1rI4OnK98CGiZjzqByfDA7dmS1SdIG9YujLT3qMX4Ycao71copAmKzaqJKr6';

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => $title,
                "body" => $body,
            ]
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
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
