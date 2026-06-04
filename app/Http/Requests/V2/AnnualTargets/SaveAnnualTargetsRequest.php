<?php

namespace App\Http\Requests\V2\AnnualTargets;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * Body for POST /deliverables/{deliverableId}/annual-targets.
 *
 * Partial-update semantics: only the entries the client sends are written;
 * untouched KPI targets are left as-is. Stricter per-entry validation
 * (kpiId belongs to the deliverable, numeric non-negative value) is
 * performed in AnnualTargetsService::save where the deliverable context
 * is available.
 */
class SaveAnnualTargetsRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'targets' => ['required', 'array', 'min:1'],
            'targets.*.kpiId' => ['required', 'string'],
            'targets.*.value' => ['required', 'string'],
        ];
    }
}
