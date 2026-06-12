<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A registered FCM device token for a user (API_REFERENCE.md §11.14).
 * Multiple rows per user are valid — one per device. Tokens are unique across
 * the table; an upsert on register handles handset re-pairing.
 */
class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'app_version',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
