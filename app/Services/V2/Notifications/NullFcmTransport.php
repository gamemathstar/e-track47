<?php

namespace App\Services\V2\Notifications;

use Illuminate\Support\Facades\Log;

/**
 * No-op transport. Used when services.fcm.credentials is unset (local dev,
 * CI, or production without a Firebase project yet). Logs the would-have-sent
 * payload at info level and records sends in-memory so tests can assert.
 *
 * Returning STATUS_SKIPPED (not SENT) tells the dispatcher this push didn't
 * actually go out — the in-app inbox row is still written so the user sees
 * the notification on their next inbox load.
 */
class NullFcmTransport implements FcmTransport
{
    /** @var array<int, array{token:string,title:string,body:string,data:array}> */
    public array $sends = [];

    public function send(string $token, string $title, string $body, array $data = []): string
    {
        $this->sends[] = compact('token', 'title', 'body', 'data');

        Log::info('notification.fcm skipped (null transport — no FIREBASE_CREDENTIALS configured)', [
            'token_prefix' => substr($token, 0, 8),
            'title' => $title,
        ]);

        return self::STATUS_SKIPPED;
    }
}
