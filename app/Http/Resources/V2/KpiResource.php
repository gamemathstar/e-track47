<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;

/**
 * KPI list + detail (API_REFERENCE.md §11.4.1 / §11.4.2). Reads the derived
 * `v_*` attributes attached by KpiTrackingService. Required summary fields are
 * always present; detail-only fields are emitted only when attached (detail path).
 */
class KpiResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        $attrs = $this->resource->getAttributes();
        $has = fn (string $key) => array_key_exists($key, $attrs);
        $val = fn (string $key) => $has($key) ? $attrs[$key] : null;

        return static::pruneNulls([
            // required summary
            'id' => (string) $this->id,
            'deliverableId' => $this->v_deliverable_id,
            'title' => $this->v_title,
            'targetLabel' => $this->v_target_label,
            'statusLabel' => $this->v_status_label,
            'status' => $this->v_status,
            'quartersOverview' => $this->v_quarters_overview,
            'lastUpdatedLabel' => $this->v_last_updated_label,
            // detail-only
            'unit' => $val('v_unit'),
            'targetValue' => $val('v_target_value'),
            'year' => $has('v_year') ? (int) $attrs['v_year'] : null,
            'breadcrumb' => $val('v_breadcrumb'),
            'parentCommitmentTitle' => $val('v_parent_commitment_title'),
            'progressPercent' => $has('v_progress_percent') ? (float) $attrs['v_progress_percent'] : null,
            'heroEyebrow' => $val('v_hero_eyebrow'),
            'heroValue' => $val('v_hero_value'),
            'heroSuffix' => $val('v_hero_suffix'),
            'heroSubtext' => $val('v_hero_subtext'),
            'activeQuarter' => $val('v_active_quarter'),
            'submissions' => $val('v_submissions'),
            'supportingDocuments' => $val('v_supporting_documents'),
            'activeMilestoneValue' => $val('v_active_milestone_value'),
            'activeTrackingDateLabel' => $val('v_active_tracking_date_label'),
        ]);
    }
}
