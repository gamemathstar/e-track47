<?php

namespace App\Services\V2;

use App\Exceptions\V2\ApiException;
use App\Models\ApiRefreshToken;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\Token;

/**
 * Auth business logic for the v2 mobile API (assumption A1).
 *
 * Access tokens are issued by Passport (so v1 is unaffected); a rotating opaque
 * refresh token is layered on top via the api_refresh_tokens table. All token
 * work is additive — nothing here touches the session-based web app.
 */
class AuthService
{
    public const ACCESS_TOKEN_NAME = 'pdcu-mobile-v2';

    public const REFRESH_TTL_DAYS = 30;

    /**
     * Verify credentials and issue a fresh session (access + refresh + user).
     *
     * @return array{access_token:string, refresh_token:string, user:User}
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw new ApiException('invalid_credentials', 'Email or password is incorrect.', 401);
        }

        return $this->issueSession($user);
    }

    /**
     * @return array{access_token:string, refresh_token:string, user:User}
     */
    public function issueSession(User $user): array
    {
        return [
            'access_token' => $user->createToken(self::ACCESS_TOKEN_NAME)->accessToken,
            'refresh_token' => $this->issueRefreshToken($user),
            'user' => $user,
        ];
    }

    /**
     * Rotate a refresh token: validate, revoke the presented one, and mint a new
     * access/refresh pair (§11.1.5).
     *
     * @return array{access_token:string, refresh_token:string}
     */
    public function refresh(string $rawRefreshToken): array
    {
        $record = ApiRefreshToken::where('token_hash', ApiRefreshToken::hashToken($rawRefreshToken))->first();

        if (! $record || ! $record->isActive() || ! $record->user) {
            throw new ApiException('invalid_refresh_token', 'The refresh token is invalid or has expired.', 401);
        }

        $user = $record->user;
        $record->revoke(); // single-use: rotate on every refresh

        return [
            'access_token' => $user->createToken(self::ACCESS_TOKEN_NAME)->accessToken,
            'refresh_token' => $this->issueRefreshToken($user),
        ];
    }

    /**
     * Revoke the current access token (when it is a real Passport token) and all
     * of the user's outstanding refresh tokens.
     */
    public function logout(User $user, ?object $currentAccessToken = null): void
    {
        if ($currentAccessToken instanceof Token) {
            $currentAccessToken->revoke();
        }

        ApiRefreshToken::where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Set a new password during the forced-change flow and clear the gate flag.
     * The model's `password` cast hashes the value on save.
     */
    public function forceChangePassword(User $user, string $newPassword): void
    {
        $user->password = $newPassword;
        $user->must_change_password = false;
        $user->save();
    }

    protected function issueRefreshToken(User $user): string
    {
        $raw = Str::random(80);

        ApiRefreshToken::create([
            'user_id' => $user->id,
            'token_hash' => ApiRefreshToken::hashToken($raw),
            'expires_at' => now()->addDays(self::REFRESH_TTL_DAYS),
        ]);

        return $raw;
    }
}
