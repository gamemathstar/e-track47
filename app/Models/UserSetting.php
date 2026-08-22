<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id', 'theme_mode', 'font_scale',
        'biometric_enabled', 'cellular_uploads_enabled', 'sync_on_wifi_only',
        'language_code', 'language_label',
    ];

    protected $casts = [
        'font_scale' => 'float',
        'biometric_enabled' => 'bool',
        'cellular_uploads_enabled' => 'bool',
        'sync_on_wifi_only' => 'bool',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
