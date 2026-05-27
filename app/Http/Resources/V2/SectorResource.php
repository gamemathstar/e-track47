<?php

namespace App\Http\Resources\V2;

use App\Support\V2\Presenters\SectorPresenter;
use Illuminate\Http\Request;

/**
 * Sector list + detail (API_REFERENCE.md §11.3.1 / §11.3.2). Detail-only fields
 * (totalCommitments, inProgressCommitments, pendingApprovals) are emitted only
 * when the service attached them (detail path); otherwise pruned.
 */
class SectorResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $attrs = $this->resource->getAttributes();
        $has = fn (string $key) => array_key_exists($key, $attrs);

        return static::pruneNulls([
            'id' => (string) $this->id,
            'name' => $this->sector_name,
            'ministry' => $this->ministry ?? '',
            'icon' => SectorPresenter::icon($this->resource),
            'progressPercent' => round((float) ($this->progress_fraction ?? 0), 4),
            'completedCommitments' => (int) ($this->completed_commitments ?? 0),
            'atRiskCommitments' => (int) ($this->at_risk_commitments ?? 0),
            'totalCommitments' => $has('total_commitments') ? (int) $attrs['total_commitments'] : null,
            'inProgressCommitments' => $has('in_progress_commitments') ? (int) $attrs['in_progress_commitments'] : null,
            'pendingApprovals' => $has('pending_approvals') ? (int) $attrs['pending_approvals'] : null,
        ]);
    }
}
