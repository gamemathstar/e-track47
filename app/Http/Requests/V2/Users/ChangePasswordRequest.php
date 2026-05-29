<?php

namespace App\Http\Requests\V2\Users;

use App\Http\Requests\V2\BaseFormRequest;

class ChangePasswordRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'currentPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8'],
        ];
    }
}
