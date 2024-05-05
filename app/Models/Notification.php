<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public static function make(User $sender,User $recipient,Model $model,$title,$body,$type,$do=1)
    {
        $notification = new Notification();
        $notification->user_id = $recipient->id;
        $notification->sender_id = $sender?$sender->id:0;
        $notification->title = $title;
        $notification->body = $body;
        $notification->type = $type;
        $notification->model_id = $model->id;
        $notification->status = 'Not Read';
        $notification->save();

        if($do){
            self::sendPushNotification($recipient,$title,$body);
        }

    }


    public static function sendPushNotification($recipient,$title,$body)
    {
        if(is_array($recipient)){
            $firebaseToken = User::whereIn('id',$recipient)->pluck('fcm_token')->all();
        }else{
            $firebaseToken = User::where('id',$recipient->id)->pluck('fcm_token')->all();
        }

        $SERVER_API_KEY = 'AIzaSyCCNbOh7aSwGIF8yRdBAui8CUkGegw1Pw4';

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
