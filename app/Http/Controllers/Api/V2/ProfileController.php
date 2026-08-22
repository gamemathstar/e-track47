<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Resources\V2\ProfileResource;
use Illuminate\Http\Request;

/**
 * Profile endpoints (API_REFERENCE.md §11.2).
 */
class ProfileController extends BaseController
{
    /** GET /profile/me — bearer. */
    public function me(Request $request): ProfileResource
    {
        return new ProfileResource($request->user());
    }
}
