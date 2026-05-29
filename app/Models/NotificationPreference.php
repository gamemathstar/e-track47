<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user notification preferences (API_REFERENCE.md §11.14.2).
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'submissions', 'approvals', 'rejections', 'mentions', 'deadlines',
        'push', 'email', 'sms',
        'quiet_hours_enabled',
        'quiet_from_hour', 'quiet_from_minute',
        'quiet_to_hour', 'quiet_to_minute',
    ];

    protected $casts = [
        'submissions' => 'bool',
        'approvals' => 'bool',
        'rejections' => 'bool',
        'mentions' => 'bool',
        'deadlines' => 'bool',
        'push' => 'bool',
        'email' => 'bool',
        'sms' => 'bool',
        'quiet_hours_enabled' => 'bool',
        'quiet_from_hour' => 'int',
        'quiet_from_minute' => 'int',
        'quiet_to_hour' => 'int',
        'quiet_to_minute' => 'int',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
