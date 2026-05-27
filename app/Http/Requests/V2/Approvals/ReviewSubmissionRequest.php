<?php

namespace App\Http\Requests\V2\Approvals;

use App\Http\Requests\V2\BaseFormRequest;

class ReviewSubmissionRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'role' => ['required', 'string', 'in:sector_head,facilitator,coordinator'],
            'decision' => ['required', 'string', 'in:accept,reject'],
            'validatedValue' => ['nullable', 'string'],
            'acceptRemarks' => ['nullable', 'string'],
            'rejectionReason' => ['nullable', 'string', 'required_if:decision,reject'],
        ];
    }
}
