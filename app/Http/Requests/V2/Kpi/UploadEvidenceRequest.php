<?php

namespace App\Http\Requests\V2\Kpi;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * POST /kpis/{id}/evidence — single-file upload from the mobile
 * "Add Performance Tracking" sheet (API_REFERENCE §11.4.8).
 *
 * Images only for v1 (jpeg/png/webp), 5 MB cap. When a document picker is
 * added on the client, expand `mimes` and bump `max`.
 */
class UploadEvidenceRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
