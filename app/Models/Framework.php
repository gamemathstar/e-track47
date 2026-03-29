<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Framework extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'title',
        'status',
        'description',
        'created_by',
        'archived_by',
        'archived_at',
    ];

    protected $casts = [
        'archived_at' => 'datetime',
    ];

    /**
     * Relationships to annual configuration entities
     */
    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }

    public function commitments()
    {
        return $this->hasMany(Commitment::class);
    }

    public function deliverables()
    {
        return $this->hasMany(Deliverable::class);
    }

    public function kpis()
    {
        return $this->hasMany(Kpi::class);
    }

    public function performanceTrackings()
    {
        return $this->hasMany(PerformanceTracking::class);
    }

    /**
     * Get the user who created the framework
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who archived the framework
     */
    public function archiver()
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * Check if framework is active
     */
    public function isActive()
    {
        return $this->status === 'Active';
    }

    /**
     * Check if framework is archived
     */
    public function isArchived()
    {
        return $this->status === 'Archived';
    }

    /**
     * Scope to get active frameworks
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'Active');
    }

    /**
     * Scope to get archived frameworks
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'Archived');
    }
}
