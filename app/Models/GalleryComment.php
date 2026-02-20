<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GalleryComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'gallery_id',
        'commenter_name',
        'phone_number',
        'email',
        'comment',
    ];

    /**
     * Get the gallery that this comment belongs to
     */
    public function gallery()
    {
        return $this->belongsTo(Gallery::class);
    }
}
