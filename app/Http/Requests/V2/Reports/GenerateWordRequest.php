<?php

namespace App\Http\Requests\V2\Reports;

use App\Http\Requests\V2\BaseFormRequest;

class GenerateWordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'sectorId' => ['nullable', 'string'],
            'year' => ['nullable', 'integer'],
            'quarter' => ['nullable', 'in:q1,q2,q3,q4'],
            'title' => ['present', 'nullable', 'string'],
            'author' => ['present', 'nullable', 'string'],
            'dateLabel' => ['present', 'nullable', 'string'],
        ];
    }
}
