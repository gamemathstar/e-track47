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
        $receiverId = UserRole::where(['role' => 'Delivery Department', 'role_status' => 'Active'])->pluck('user_id');
        $receiver = User::whereIn('id', $receiverId)->first();

        $body = $user->role()->role . ' of ' . $user->sector()->sector_name . ' made a submission on ' . $tracking->kpi->kpi . '. It awaits your review';
        $forme = 'Your request on ' . $tracking->kpi->kpi . ' has been submitted to Delivery Department. It is waiting for review';

        self::make($user, $receiver, $tracking, 'Review Request', $body, 'Tracking Submitted');
        self::make($receiver, $user, $tracking, 'Tracking Submitted', $forme, 'System');
    }

    public static function submitTrackingReview($tracking)
    {
        $user = Auth::user();
        $receiverId = $tracking->kpi->deliverable->commitment->sector->sector_head_id; // ->sector_name
        $receiver = User::find($receiverId);

        $body = 'Delivery department ' . $tracking->confirmation_status . ' your submission on '
            . $tracking->kpi->kpi . '.';
        $forme = 'Your review on ' . $tracking->kpi->kpi . ' has been submitted to ' . $receiver->role()->role
            . ' of ' . $receiver->sector()->sector_name;

        self::make($user, $receiver, $tracking, 'Tracking Reviewed', $body, 'Tracking Reviewed');
        self::make($receiver, $user, $tracking, 'Tracking Reviewed', $forme, 'System');
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
