<?php

namespace App\Services\V2\Notifications;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

/**
 * Real FCM HTTP v1 transport via kreait/laravel-firebase. Auth is OAuth2,
 * minted from the service-account JSON pointed to by services.fcm.credentials.
 */
class KreaitFcmTransport implements FcmTransport
{
    public function send(string $token, string $title, string $body, array $data = []): string
    {
        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FirebaseNotification::create($title, $body))
                ->withData($this->coerceDataPayload($data));

            Firebase::messaging()->send($message);
            return self::STATUS_SENT;
        } catch (NotFound $e) {
            // Token unregistered or unknown — the dispatcher should delete the
            // device_tokens row so it doesn't keep getting tried.
            return self::STATUS_UNREGISTERED;
        } catch (MessagingException $e) {
            // 400-class invalid argument vs. 5xx / 429 transient — kreait
            // surfaces both as MessagingException; the HTTP code on the
            // response tells them apart.
            $code = method_exists($e, 'code') ? $e->code() : 0;
            if ($code >= 500 || $code === 429) {
                return self::STATUS_RETRYABLE;
            }
            Log::warning('FCM send rejected', ['code' => $code, 'message' => $e->getMessage()]);
            return self::STATUS_INVALID;
        } catch (Throwable $e) {
            Log::warning('FCM send failed (unexpected)', ['message' => $e->getMessage()]);
            return self::STATUS_RETRYABLE;
        }
    }

    /**
     * FCM v1 `data` payload must be a flat object of string→string. Coerce
     * scalars and json-encode anything else so the dispatcher can pass through
     * `deepLinkParams` etc. without worrying about the wire shape.
     */
    private function coerceDataPayload(array $data): array
    {
        $out = [];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $out[$key] = $value;
            } elseif (is_scalar($value)) {
                $out[$key] = (string) $value;
            } elseif ($value !== null) {
                $out[$key] = (string) json_encode($value);
            }
        }

        return $out;
    }
}
