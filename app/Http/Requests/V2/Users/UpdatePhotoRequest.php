<?php

namespace App\Http\Requests\V2\Users;

use App\Http\Requests\V2\BaseFormRequest;

class UpdatePhotoRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'max:5120'],
        ];
    }
}
