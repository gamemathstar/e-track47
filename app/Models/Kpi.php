<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    use HasFactory;

    protected $fillable = [
        'deliverable_id',
        'framework_id',
        'kpi',
        'target_value',
        'unit_of_measurement',
        'year',
    ];

    public function deliverable()
    {
        return $this->belongsTo(Deliverable::class);
    }

    public function framework()
    {
        return $this->belongsTo(Framework::class);
    }

    public function performanceTracking()
    {
        return $this->hasMany(PerformanceTracking::class)->orderBy('quarter','ASC');
    }

    public function status()
    {
        $track = $this->performanceTracking()->first();
        return $track ? $track->status() : '';
    }

    public function kpiTargets($year=null)
    {
        if($year)
            return $this->kpiTargets()->where('year',$year);
        return $this->hasMany(KpiTarget::class);
    }

    public function quarter($quarter=1)
    {
        return $this->performanceTracking()->where('quarter',$quarter)->first();
    }

    /**
     * Get performance tracking for a specific quarter and year
     *
     * @param int $quarter Quarter number (1-4)
     * @param int $year Year
     * @param bool $onlyApproved If true, only return records approved by Sector Head
     * @return PerformanceTracking|null
     */
    public function getQuarterTrack($quarter, $year, $onlyApproved = false)
    {
        $query = $this->performanceTracking()
            ->where('quarter', $quarter)
            ->where('year', $year);
        
        if ($onlyApproved) {
            $query->whereNotNull('sector_head_approved_at');
        }
        
        return $query->first();
    }

    /**
     * Get all performance tracking records for a specific year
     *
     * @param int $year Year
     * @param bool $onlyApproved If true, only return records approved by Sector Head
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getYearTracks($year, $onlyApproved = false)
    {
        $query = $this->performanceTracking()
            ->where('year', $year);
        
        if ($onlyApproved) {
            $query->whereNotNull('sector_head_approved_at');
        }
        
        return $query->orderBy('quarter', 'ASC')->get();
    }
}
