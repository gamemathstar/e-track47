<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Gallery extends Model
{
    use HasFactory;

    protected $fillable = [
        'image_path',
        'title',
        'caption',
        'status',
        'display_order',
        'uploaded_by',
    ];

    protected $casts = [
        'display_order' => 'integer',
    ];

    /**
     * Get the user who uploaded this gallery item
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Scope to get only active gallery items
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to order by display order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc')->orderBy('created_at', 'desc');
    }

    /**
     * Get all comments for this gallery item
     */
    public function comments()
    {
        return $this->hasMany(GalleryComment::class)->orderBy('created_at', 'desc');
    }
}
