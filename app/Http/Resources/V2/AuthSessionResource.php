<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Request;

/**
 * The login response (API_REFERENCE.md §11.1.1): snake_case token keys plus a
 * nested user object. Wraps the array produced by AuthService::issueSession()
 * (keys: access_token, refresh_token, user).
 */
class AuthSessionResource extends BaseResource
{
    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->resource['access_token'],
            'refresh_token' => $this->resource['refresh_token'],
            'user' => UserResource::make($this->resource['user'])->resolve($request),
        ];
    }
}
