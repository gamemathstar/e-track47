<?php

namespace App\Services\V2\Notifications;

/**
 * Push transport contract — sends one notification to one device token at a
 * time. Two implementations:
 *
 *  - KreaitFcmTransport  — real network send via the FCM HTTP v1 API.
 *  - NullFcmTransport    — no-op fallback used in tests / when no service
 *                          account is configured. Records sends in-memory so
 *                          tests can assert without touching the network.
 *
 * The contract returns an enum-like status so the dispatcher can prune dead
 * tokens (delete the device_tokens row on `unregistered`).
 */
interface FcmTransport
{
    public const STATUS_SENT = 'sent';
    public const STATUS_UNREGISTERED = 'unregistered'; // token revoked / app uninstalled — delete it
    public const STATUS_INVALID = 'invalid'; // malformed token — delete it
    public const STATUS_RETRYABLE = 'retryable'; // transient (rate limit, 5xx) — let the queue retry
    public const STATUS_SKIPPED = 'skipped'; // no transport configured — never tried

    public function send(string $token, string $title, string $body, array $data = []): string;
}
