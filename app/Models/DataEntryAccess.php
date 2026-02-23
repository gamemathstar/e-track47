<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataEntryAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'sector_id',
        'year',
        'quarter',
        'deadline_date',
        'status',
        'override_deadline',
        'override_reason',
        'granted_by',
        'granted_at',
    ];

    protected $casts = [
        'deadline_date' => 'date',
        'override_deadline' => 'date',
        'granted_at' => 'datetime',
        'year' => 'integer',
        'quarter' => 'integer',
    ];

    /**
     * Get the sector that owns this access record
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    /**
     * Get the user who granted the override
     */
    public function grantedBy()
    {
        return $this->belongsTo(User::class, 'granted_by');
    }

    /**
     * Check if data entry is currently open for this access record
     */
    public function isOpen()
    {
        if ($this->status === 'closed') {
            return false;
        }

        $deadline = $this->override_deadline ?? $this->deadline_date;
        return Carbon::now()->lte($deadline);
    }

    /**
     * Check if data entry window has expired
     */
    public function isExpired()
    {
        $deadline = $this->override_deadline ?? $this->deadline_date;
        return Carbon::now()->gt($deadline);
    }

    /**
     * Calculate the normal deadline date (2 weeks after quarter end)
     */
    public static function calculateDeadline($year, $quarter)
    {
        $quarterEndDates = [
            1 => Carbon::createFromDate($year, 3, 31),  // Q1 ends March 31
            2 => Carbon::createFromDate($year, 6, 30),  // Q2 ends June 30
            3 => Carbon::createFromDate($year, 9, 30),  // Q3 ends September 30
            4 => Carbon::createFromDate($year, 12, 31), // Q4 ends December 31
        ];

        if (!isset($quarterEndDates[$quarter])) {
            throw new \InvalidArgumentException("Invalid quarter: {$quarter}");
        }

        // Add 2 weeks (14 days) to quarter end date
        return $quarterEndDates[$quarter]->copy()->addDays(14);
    }

    /**
     * Get current quarter based on today's date
     */
    public static function getCurrentQuarter()
    {
        $month = Carbon::now()->month;
        if ($month >= 1 && $month <= 3) return 1;
        if ($month >= 4 && $month <= 6) return 2;
        if ($month >= 7 && $month <= 9) return 3;
        return 4;
    }

    /**
     * Get current year
     */
    public static function getCurrentYear()
    {
        return Carbon::now()->year;
    }

    /**
     * Check if data entry is allowed for a sector/quarter/year
     */
    public static function isDataEntryAllowed($sectorId, $year = null, $quarter = null)
    {
        $year = $year ?? self::getCurrentYear();
        $quarter = $quarter ?? self::getCurrentQuarter();

        $access = self::where('sector_id', $sectorId)
            ->where('year', $year)
            ->where('quarter', $quarter)
            ->first();

        if (!$access) {
            // If no access record exists, check if we're within the normal window
            $deadline = self::calculateDeadline($year, $quarter);
            return Carbon::now()->lte($deadline);
        }

        return $access->isOpen();
    }

    /**
     * Scope to get open access records
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open')
            ->where(function($q) {
                $q->where('override_deadline', '>=', Carbon::now())
                  ->orWhere(function($q2) {
                      $q2->whereNull('override_deadline')
                         ->where('deadline_date', '>=', Carbon::now());
                  });
            });
    }

    /**
     * Scope to get closed access records
     */
    public function scopeClosed($query)
    {
        return $query->where(function($q) {
            $q->where('status', 'closed')
              ->orWhere(function($q2) {
                  $q2->where('status', 'open')
                     ->where(function($q3) {
                         $q3->where('override_deadline', '<', Carbon::now())
                            ->orWhere(function($q4) {
                                $q4->whereNull('override_deadline')
                                   ->where('deadline_date', '<', Carbon::now());
                            });
                     });
              });
        });
    }
}
