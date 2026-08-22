<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Services\V2\Notifications\FcmTransport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Sends one FCM message to one device token, off the request thread.
 *
 * - On `unregistered` / `invalid` the stale device_tokens row is deleted so
 *   we don't keep trying.
 * - On `retryable` the job throws so Laravel re-queues with backoff.
 * - `sent` / `skipped` complete silently.
 */
class SendFcmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30; // seconds

    public function __construct(
        public readonly int $deviceTokenId,
        public readonly string $token,
        public readonly string $title,
        public readonly string $body,
        public readonly array $data = [],
    ) {
    }

    public function handle(FcmTransport $transport): void
    {
        $status = $transport->send($this->token, $this->title, $this->body, $this->data);

        switch ($status) {
            case FcmTransport::STATUS_UNREGISTERED:
            case FcmTransport::STATUS_INVALID:
                DeviceToken::where('id', $this->deviceTokenId)->delete();
                return;
            case FcmTransport::STATUS_RETRYABLE:
                $this->release($this->backoff);
                return;
            default:
                return;
        }
    }
}
