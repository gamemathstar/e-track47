<?php

namespace App\Http\Requests\V2\Settings;

use App\Http\Requests\V2\BaseFormRequest;

class UpdateSettingsPreferencesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'themeMode' => ['required', 'string', 'in:system,light,dark'],
            'fontScale' => ['required', 'numeric', 'between:0,1'],
            'biometricEnabled' => ['required', 'boolean'],
            'cellularUploadsEnabled' => ['required', 'boolean'],
            'syncOnWifiOnly' => ['required', 'boolean'],
            'languageCode' => ['required', 'string', 'max:10'],
            'languageLabel' => ['required', 'string', 'max:64'],
            'appVersion' => ['nullable', 'string'],
        ];
    }
}
