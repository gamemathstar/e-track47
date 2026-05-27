<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Opaque, rotating refresh token for the v2 mobile API (assumption A1).
 *
 * The raw token is returned to the client once at issue/rotate time; only its
 * SHA-256 hash is stored. `/auth/refresh` looks the token up by hash, checks it
 * is unexpired and unrevoked, then rotates it (revokes the old, issues a new) and
 * mints a fresh Passport access token. This is purely additive — the web app and
 * v1 do not use this table.
 */
class ApiRefreshToken extends Model
{
    protected $fillable = [
        'user_id',
        'token_hash',
        'expires_at',
        'revoked_at',
        'last_used_at',
        'device_label',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }

    public function revoke(): void
    {
        if ($this->revoked_at === null) {
            $this->forceFill(['revoked_at' => now()])->save();
        }
    }
}
