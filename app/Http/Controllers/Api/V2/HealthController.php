<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * Auth-free deploy/CI probe (`GET /api/v2/_health`). Returns 200 when the v2
 * lane is ready to issue and verify tokens, or 503 with diagnostics when it
 * isn't. Exposes only non-sensitive metadata (no file paths, no secrets, no
 * row counts) — safe to leave reachable by the mobile team and load balancers.
 *
 * Symptoms it catches before users do:
 *   - `passport: missing_keys`   — `php artisan pdcu:ensure-passport-keys`
 *   - `passport: missing_client` — `php artisan passport:install --no-interaction`
 *   - `db: fail`                 — DB unreachable from the app
 */
class HealthController extends BaseController
{
    public function show(): JsonResponse
    {
        $passport = $this->passportStatus();
        $db = $this->dbStatus();
        $healthy = $passport === 'ok' && $db === 'ok';

        return response()->json([
            'api' => 'v2.0.0',
            'passport' => $passport,
            'db' => $db,
        ], $healthy ? 200 : 503);
    }

    private function passportStatus(): string
    {
        $keyPath = storage_path('oauth-private.key');
        if (! file_exists($keyPath) || filesize($keyPath) < 500) {
            return 'missing_keys';
        }

        try {
            if (DB::table('oauth_personal_access_clients')->count() === 0) {
                return 'missing_client';
            }
        } catch (\Throwable) {
            // If the DB probe below returns 'fail' we'll surface that; for
            // Passport specifically, a missing/unreachable oauth table means
            // there's no usable client either way.
            return 'missing_client';
        }

        return 'ok';
    }

    private function dbStatus(): string
    {
        try {
            DB::select('SELECT 1');

            return 'ok';
        } catch (\Throwable) {
            return 'fail';
        }
    }
}
