<?php

namespace App\Http\Requests\V2\Settings;

use App\Http\Requests\V2\BaseFormRequest;

class FeedbackRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'screenshot' => ['nullable', 'file', 'image', 'max:5120'],
        ];
    }
}
