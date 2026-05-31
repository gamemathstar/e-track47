<?php

namespace App\Http\Requests\V2\DataEntry;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * Body for POST /data-entry/windows/lock-all — locks every sector window
 * for the named period (year + quarter, both required).
 */
class LockAllRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'quarter' => ['required', 'in:q1,q2,q3,q4'],
        ];
    }
}
