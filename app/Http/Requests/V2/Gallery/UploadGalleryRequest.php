<?php

namespace App\Http\Requests\V2\Gallery;

use App\Http\Requests\V2\BaseFormRequest;

class UploadGalleryRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'category' => ['required', 'string', 'in:infrastructure,education,health,agriculture'],
            'displayOrder' => ['required', 'integer', 'min:0'],
            'isPublic' => ['required'],
            'asset' => ['nullable', 'file', 'image', 'max:10240'],
        ];
    }
}
