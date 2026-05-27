<?php

namespace App\Http\Requests\V2;

use App\Exceptions\V2\ApiException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

/**
 * Base for all v2 FormRequests. Centralizes validation so controllers stay thin.
 *
 * Validation/authorization failures are thrown as exceptions that the
 * route-scoped handler renders into the §6 contract
 * ({ code, message, fieldErrors? }) with the correct HTTP status — regardless of
 * the client's Accept header.
 */
abstract class BaseFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Always throw ValidationException (never redirect), so the v2 handler can
     * render 422 + fieldErrors even if the Accept header is missing.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new ValidationException($validator);
    }

    protected function failedAuthorization(): void
    {
        throw ApiException::forbidden();
    }
}
