<?php

namespace App\Http\Requests\V2\Frameworks;

use App\Http\Requests\V2\BaseFormRequest;

class CreateFrameworkRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sectorMethod' => ['required', 'string', 'in:blank,inherit'],
            'reportingYear' => ['nullable', 'integer'],
            'description' => ['nullable', 'string'],
            'subtitle' => ['nullable', 'string'],
            'inheritedFromFrameworkId' => ['nullable', 'string', 'required_if:sectorMethod,inherit'],
        ];
    }
}
