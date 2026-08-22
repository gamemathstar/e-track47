<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Requests\V2\Auth\ForcePasswordChangeRequest;
use App\Http\Requests\V2\Auth\LoginRequest;
use App\Http\Requests\V2\Auth\RefreshRequest;
use App\Http\Resources\V2\AuthSessionResource;
use App\Http\Resources\V2\UserResource;
use App\Services\V2\AuthService;
use Illuminate\Http\Request;

/**
 * Authentication endpoints (API_REFERENCE.md §11.1). Thin: validates via
 * FormRequests, delegates to AuthService, returns raw Resources / 204.
 */
class AuthController extends BaseController
{
    public function __construct(private readonly AuthService $auth)
    {
    }

    /** POST /auth/login — public. */
    public function login(LoginRequest $request): AuthSessionResource
    {
        $data = $request->validated();

        return new AuthSessionResource(
            $this->auth->login($data['email'], $data['password'])
        );
    }

    /** POST /auth/refresh — public; rotates the refresh token. */
    public function refresh(RefreshRequest $request): array
    {
        // Raw { access_token, refresh_token } object (no envelope).
        return $this->auth->refresh($request->validated()['refresh_token']);
    }

    /** GET /auth/me — bearer. */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /** POST /auth/logout — bearer. */
    public function logout(Request $request)
    {
        $this->auth->logout($request->user(), $request->user()->token());

        return $this->noContent();
    }

    /** POST /auth/password/force-change — bearer. */
    public function forcePasswordChange(ForcePasswordChangeRequest $request)
    {
        $this->auth->forceChangePassword($request->user(), $request->validated()['newPassword']);

        return $this->noContent();
    }
}
