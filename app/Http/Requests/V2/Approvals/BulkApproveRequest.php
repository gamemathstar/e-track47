<?php

namespace App\Http\Requests\V2\Approvals;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * POST /approvals/submissions/bulk-approve — accepts sector_head or coordinator
 * (facilitator review is single-item only). Service layer enforces atomicity:
 * if any id isn't approvable for the named role, the whole call 409s and no
 * row is touched.
 */
class BulkApproveRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'submissionIds' => ['required', 'array', 'min:1'],
            'submissionIds.*' => ['required', 'string'],
            'role' => ['required', 'string', 'in:sector_head,coordinator'],
        ];
    }
}
