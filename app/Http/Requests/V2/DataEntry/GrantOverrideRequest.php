<?php

namespace App\Http\Requests\V2\DataEntry;

use App\Http\Requests\V2\BaseFormRequest;

class GrantOverrideRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string'],
            'expiresAt' => ['nullable', 'string'],
        ];
    }
}
