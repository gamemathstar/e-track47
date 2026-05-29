<?php

namespace App\Http\Requests\V2\Notifications;

use App\Http\Requests\V2\BaseFormRequest;

class UpdateNotificationPreferencesRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'submissions' => ['required', 'boolean'],
            'approvals' => ['required', 'boolean'],
            'rejections' => ['required', 'boolean'],
            'mentions' => ['required', 'boolean'],
            'deadlines' => ['required', 'boolean'],
            'push' => ['required', 'boolean'],
            'email' => ['required', 'boolean'],
            'sms' => ['required', 'boolean'],
            'quietHoursEnabled' => ['required', 'boolean'],
            'quietFrom' => ['required', 'array'],
            'quietFrom.hour' => ['required', 'integer', 'between:0,23'],
            'quietFrom.minute' => ['required', 'integer', 'between:0,59'],
            'quietTo' => ['required', 'array'],
            'quietTo.hour' => ['required', 'integer', 'between:0,23'],
            'quietTo.minute' => ['required', 'integer', 'between:0,59'],
        ];
    }
}
