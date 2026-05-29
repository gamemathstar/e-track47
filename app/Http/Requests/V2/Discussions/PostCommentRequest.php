<?php

namespace App\Http\Requests\V2\Discussions;

use App\Http\Requests\V2\BaseFormRequest;

class PostCommentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'min:1'],
            'parentId' => ['nullable', 'string'],
        ];
    }
}
