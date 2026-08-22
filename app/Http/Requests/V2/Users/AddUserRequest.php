<?php

namespace App\Http\Requests\V2\Users;

use App\Http\Requests\V2\BaseFormRequest;

class AddUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'fullName' => ['required', 'string', 'max:222'],
            'email' => ['required', 'email', 'max:222'],
            'phone' => ['required', 'string', 'max:32'],
            'role' => ['required', 'string', 'in:governor,coordinator,sector_head,data_admin,facilitator,system_admin'],
            'avatarKey' => ['nullable', 'string', 'max:64'],
            'photo' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
