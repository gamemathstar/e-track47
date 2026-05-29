<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionThread extends Model
{
    protected $fillable = [
        'sector_id', 'title', 'status', 'status_label',
        'lead_name', 'lead_label', 'lead_initials',
        'preview_body', 'author_name',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(DiscussionComment::class, 'thread_id');
    }
}
