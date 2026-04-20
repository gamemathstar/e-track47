<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

class PerformanceTracking extends Model
{
    use HasFactory;

//    protected $table = 'performance_tracking';

    protected $fillable = [
        'kpi_id',
        'framework_id',
        'quarter',
        'year',
        'milestone',
        'tracking_date',
        'actual_value',
        'remarks',
        'delivery_department_value',
        'delivery_department_remark',
        'confirmation_status',
        'sector_head_approved_at',
        'sector_head_approved_by',
        'facilitator_confirmed_at',
        'facilitator_confirmed_by',
        'facilitator_decision',
        'facilitator_rejection_reason',
        'coordinator_confirmed_at',
        'coordinator_confirmed_by',
        'coordinator_decision',
        'coordinator_rejection_reason',
    ];

    protected $casts = [
        'sector_head_approved_at' => 'datetime',
        'facilitator_confirmed_at' => 'datetime',
        'coordinator_confirmed_at' => 'datetime',
        'tracking_date' => 'date',
    ];

    public function kpi()
    {
        return $this->belongsTo(Kpi::class);
    }

    public function framework()
    {
        return $this->belongsTo(Framework::class);
    }

    public function status()
    {
        return $this->confirmation_status;
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function attachments($id)
    {
        $user = Auth::user();
        $tracking = PerformanceTracking::find($id);
        
        if (!$tracking) {
            return view('pages.sector.ajax.attachments', ['files' => collect()]);
        }
        
        // For PDCU users, only allow viewing attachments if the record is approved by Sector Head
        if ($user->isDeliveryUnit() && !$tracking->isVisibleToPDCU()) {
            return view('pages.sector.ajax.attachments', ['files' => collect()]);
        }
        
        // Get all files for this tracking record regardless of who attached them
        // This allows PDCU users to see files attached by Data Admin and vice versa
        $files = $tracking->files()->get();

        return view('pages.sector.ajax.attachments', ['files' => $files]);
    }

    /**
     * Get the user who approved as Sector Head
     */
    public function sectorHeadApprovedBy()
    {
        return $this->belongsTo(User::class, 'sector_head_approved_by');
    }

    /**
     * Get the user who confirmed as Facilitator
     */
    public function facilitatorConfirmedBy()
    {
        return $this->belongsTo(User::class, 'facilitator_confirmed_by');
    }

    /**
     * Get the user who confirmed as Coordinator
     */
    public function coordinatorConfirmedBy()
    {
        return $this->belongsTo(User::class, 'coordinator_confirmed_by');
    }

    /**
     * Check if data is visible to PDCU (must be approved by Sector Head)
     */
    public function isVisibleToPDCU()
    {
        return $this->sector_head_approved_at !== null;
    }

    /**
     * Check if data is locked from sector modification (confirmed by Coordinator)
     */
    public function isLockedFromSectorModification()
    {
        return $this->confirmation_status === 'Confirmed' && $this->coordinator_confirmed_at !== null;
    }

    /**
     * Check if pending Sector Head approval
     */
    public function isPendingSectorHeadApproval()
    {
        return $this->confirmation_status === 'Pending Sector Head Approval';
    }

    /**
     * Check if pending Facilitator
     */
    public function isPendingFacilitator()
    {
        return $this->confirmation_status === 'Pending Facilitator';
    }

    /**
     * Check if pending Coordinator
     */
    public function isPendingCoordinator()
    {
        return $this->confirmation_status === 'Pending Coordinator';
    }

    /**
     * Facilitator accepted; coordinator has not yet approved or rejected.
     */
    public function isAwaitingCoordinatorFinalApproval(): bool
    {
        return (bool) $this->sector_head_approved_by
            && $this->facilitator_decision === 'Accept'
            && (bool) $this->facilitator_confirmed_by
            && !$this->coordinator_confirmed_by;
    }
}
