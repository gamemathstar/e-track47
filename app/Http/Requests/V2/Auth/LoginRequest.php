<?php

namespace App\Http\Requests\V2\Auth;

use App\Http\Requests\V2\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
