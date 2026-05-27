<?php

namespace App\Http\Requests\V2\Auth;

use App\Http\Requests\V2\BaseFormRequest;

class RefreshRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'refresh_token' => ['required', 'string'],
        ];
    }
}
