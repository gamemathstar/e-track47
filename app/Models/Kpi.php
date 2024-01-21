<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverable_id',
        'kpi',
        'target_value',
        'start_date',
        'end_date',
        'unit_of_measurement',
    ];

    public function deliverable()
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function performanceTracking()
    {
        return $this->hasMany(PerformanceTracking::class);
    }
}
