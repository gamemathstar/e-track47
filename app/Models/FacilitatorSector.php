<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacilitatorSector extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_role_id',
        'sector_id',
    ];

    /**
     * Get the user role that owns this facilitator sector assignment
     */
    public function userRole()
    {
        return $this->belongsTo(UserRole::class);
    }

    /**
     * Get the sector assigned to this facilitator
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }
}
