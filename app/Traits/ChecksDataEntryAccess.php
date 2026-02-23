<?php

namespace App\Traits;

use App\Models\DataEntryAccess;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Kpi;
use Illuminate\Http\Request;

trait ChecksDataEntryAccess
{
    /**
     * Check if data entry is allowed for the current request
     * Returns true if allowed, false otherwise
     */
    protected function isDataEntryAllowed($sectorId, $year = null, $quarter = null)
    {
        // PDCU Coordinators and Deputy Coordinators can always access
        $user = auth()->user();
        if ($user && ($user->isCoordinator() || $user->isDeputyCoordinator())) {
            return true;
        }

        return DataEntryAccess::isDataEntryAllowed($sectorId, $year, $quarter);
    }

    /**
     * Get sector ID from commitment
     */
    protected function getSectorIdFromCommitment($commitmentId)
    {
        $commitment = Commitment::find($commitmentId);
        return $commitment ? $commitment->sector_id : null;
    }

    /**
     * Get sector ID from deliverable
     */
    protected function getSectorIdFromDeliverable($deliverableId)
    {
        $deliverable = Deliverable::with('commitment')->find($deliverableId);
        return $deliverable && $deliverable->commitment ? $deliverable->commitment->sector_id : null;
    }

    /**
     * Get sector ID from KPI
     */
    protected function getSectorIdFromKpi($kpiId)
    {
        $kpi = Kpi::with('deliverable.commitment')->find($kpiId);
        return $kpi && $kpi->deliverable && $kpi->deliverable->commitment ? $kpi->deliverable->commitment->sector_id : null;
    }

    /**
     * Check data entry access and return error response if not allowed
     */
    protected function checkDataEntryAccess($sectorId, $year = null, $quarter = null)
    {
        if (!$this->isDataEntryAllowed($sectorId, $year, $quarter)) {
            $deadline = DataEntryAccess::calculateDeadline($year ?? DataEntryAccess::getCurrentYear(), $quarter ?? DataEntryAccess::getCurrentQuarter());
            return redirect()->back()->with('failure', 
                'Data entry is closed for this quarter. The deadline was ' . $deadline->format('M d, Y') . 
                '. Please contact PDCU Coordinator or Deputy Coordinator to request access.');
        }
        return null;
    }
}
