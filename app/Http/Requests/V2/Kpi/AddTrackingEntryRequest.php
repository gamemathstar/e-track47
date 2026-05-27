<?php

namespace App\Http\Requests\V2\Kpi;

use App\Http\Requests\V2\BaseFormRequest;

class AddTrackingEntryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'quarter' => ['required', 'string', 'in:q1,q2,q3,q4'],
            'year' => ['required', 'integer'],
            'trackingDate' => ['required', 'string'],
            'actualValue' => ['required', 'string'],
            'evidenceDocumentIds' => ['nullable', 'array'],
            'evidenceDocumentIds.*' => ['string'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}
