<?php

namespace App\Http\Requests\V2\Reports;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * §11.8.8 — `POST /reports/word-document` request body.
 *
 * Mirrors the web's `/reports/word/generate` form (sector, year, observations,
 * recommendations, signatures) — minus the inline streaming. Sector id is
 * accepted as a string for consistency with every other sector-scoped
 * v2 endpoint (see §11.8.7).
 */
class GenerateWordDocumentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'sector_id' => ['required', 'string', 'exists:sectors,id'],
            'year' => ['required', 'integer', 'digits:4'],
            'observations' => ['nullable', 'string', 'max:10000'],
            'recommendations' => ['nullable', 'string', 'max:10000'],
            'pdcu_coordinator_signature' => ['nullable', 'string', 'max:255'],
            'pdcu_coordinator_date' => ['nullable', 'date'],
            'sector_facilitator_signature' => ['nullable', 'string', 'max:255'],
            'sector_facilitator_date' => ['nullable', 'date'],
        ];
    }
}
