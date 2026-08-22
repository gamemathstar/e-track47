<?php

namespace App\Http\Requests\V2\DataEntry;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * Body for POST /data-entry/windows/unlock-all — bulk-grants override access
 * to every sector for the named period. `reason` is mandatory (audit trail),
 * `expiresAt` optionally caps the override window.
 */
class UnlockAllRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'quarter' => ['required', 'in:q1,q2,q3,q4'],
            'expiresAt' => ['nullable', 'string'],
        ];
    }
}
