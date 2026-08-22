<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscussionComment extends Model
{
    protected $fillable = [
        'thread_id', 'parent_id', 'user_id',
        'author_name', 'author_role', 'author_initials',
        'body', 'like_count',
    ];

    protected $casts = [
        'like_count' => 'int',
    ];

    public function thread(): BelongsTo
    {
        return $this->belongsTo(DiscussionThread::class, 'thread_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(DiscussionCommentLike::class, 'comment_id');
    }
}
