<?php

namespace App\Http\Resources\V2;

use App\Support\V2\WireEnums;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Deliverable list + detail (API_REFERENCE.md §11.3.5 / §11.3.6).
 */
class DeliverableResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $progress = round((float) ($this->progress_fraction ?? 0), 4);

        return static::pruneNulls([
            'id' => (string) $this->id,
            'commitmentId' => (string) $this->commitment_id,
            'title' => $this->deliverable,
            'status' => WireEnums::deliverableStatusToWire($this->status),
            'kpiCount' => (int) ($this->kpi_count ?? 0),
            'progressPercent' => $progress,
            'parentCommitmentTitle' => $this->parent_commitment_title ?: null,
            'avgProgress' => $progress,
            'lastUpdated' => $this->last_updated_at ? Carbon::parse($this->last_updated_at)->toIso8601String() : null,
        ]);
    }
}
