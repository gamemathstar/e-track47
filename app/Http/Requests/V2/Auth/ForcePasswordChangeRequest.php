<?php

namespace App\Http\Requests\V2\Auth;

use App\Http\Requests\V2\BaseFormRequest;

class ForcePasswordChangeRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'newPassword' => ['required', 'string', 'min:8'],
        ];
    }
}
