<?php

namespace App\Http\Requests\V2\Gallery;

use App\Http\Requests\V2\BaseFormRequest;

/**
 * §11.13.5 — `POST /gallery/items/{id}/comments` request body.
 *
 * Unauthenticated public submit. Held for moderation server-side; the response
 * is `204 No Content` (or `202 Accepted` — both are acceptable to the client),
 * never the created row.
 */
class SubmitGalleryCommentRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'authorName' => ['required', 'string', 'min:1', 'max:120'],
            'body' => ['required', 'string', 'min:1', 'max:2000'],
        ];
    }
}
