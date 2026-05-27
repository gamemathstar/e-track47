<?php

namespace App\Http\Resources\V2;

use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Commitment list + detail (API_REFERENCE.md §11.3.3 / §11.3.4). The metric query
 * computes the detail fields for list rows too; they are harmless extras the
 * client ignores, so we include them uniformly.
 */
class CommitmentResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $deliverableCount = (int) ($this->deliverable_count ?? 0);
        $completed = (int) ($this->completed_deliverables ?? 0);

        return static::pruneNulls([
            'id' => (string) $this->id,
            'sectorId' => (string) $this->sector_id,
            'title' => $this->name,
            'status' => WireEnums::commitmentStatusToWire($this->status),
            'progressPercent' => round((float) ($this->progress_fraction ?? 0), 4),
            'kpiCount' => (int) ($this->kpi_count ?? 0),
            'dueDate' => $this->formatDate($this->next_milestone),
            'description' => $this->description ?: null,
            'deliverableCount' => $deliverableCount,
            'atRiskCount' => (int) ($this->at_risk_count ?? 0),
            'completionStatus' => $deliverableCount > 0 ? "{$completed} of {$deliverableCount}" : null,
            'nextMilestone' => $this->formatDate($this->next_milestone),
        ]);
    }

    private function formatDate($value): ?string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d') : null;
    }
}
