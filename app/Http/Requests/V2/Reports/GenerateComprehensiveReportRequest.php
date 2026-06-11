<?php

namespace App\Http\Requests\V2\Reports;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * §11.8.7 — `POST /reports/comprehensive-report` request body.
 *
 * Mirrors the web's comprehensive download/print form (start_quarter,
 * end_quarter, year, sectors[]) and adds `type` to pick excel vs pdf.
 */
class GenerateComprehensiveReportRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'sectors' => ['nullable', 'array'],
            'sectors.*' => ['integer', 'exists:sectors,id'],
            'year' => ['required', 'integer', 'digits:4'],
            'start_quarter' => ['required', 'integer', 'between:1,4'],
            'end_quarter' => ['required', 'integer', 'between:1,4', 'gte:start_quarter'],
            'type' => ['required', 'in:excel,pdf'],
        ];
    }
}
