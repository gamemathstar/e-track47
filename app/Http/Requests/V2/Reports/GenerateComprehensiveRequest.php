<?php

namespace App\Http\Requests\V2\Reports;

use App\Http\Requests\V2\BaseFormRequest;

class GenerateComprehensiveRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'sectorIds' => ['present', 'array'],
            'sectorIds.*' => ['string'],
            'year' => ['nullable', 'integer'],
            'quarter' => ['nullable', 'in:q1,q2,q3,q4'],
            'includeEvidence' => ['required', 'boolean'],
            'format' => ['required', 'in:excel,word,pdf,print'],
        ];
    }
}
