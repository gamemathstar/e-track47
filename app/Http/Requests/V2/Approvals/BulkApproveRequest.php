<?php

namespace App\Http\Requests\V2\Approvals;

use App\Http\Requests\V2\BaseFormRequest;

class BulkApproveRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'submissionIds' => ['required', 'array', 'min:1'],
            'submissionIds.*' => ['required', 'string'],
            'role' => ['required', 'string', 'in:sector_head,facilitator,coordinator'],
        ];
    }
}
