<?php

use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\CommitmentController;
use App\Http\Controllers\Api\V2\DeliverableController;
use App\Http\Controllers\Api\V2\KpiController;
use App\Http\Controllers\Api\V2\ProfileController;
use App\Http\Controllers\Api\V2\SectorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v2 routes (mobile)
|--------------------------------------------------------------------------
|
| Loaded by RouteServiceProvider at the URL prefix `api/v2` with the `api`
| middleware group. This file is the home for the new mobile API described in
| docs/API_REFERENCE.md. It is completely separate from routes/api.php (v1),
| which is left untouched (A8).
|
| Conventions:
|  - Responses are raw (no envelope); errors use the route-scoped handler.
|  - Protected routes use `auth:api` (Passport). Public-capable system signals
|    use the `auth.optional` alias.
|  - Feature route groups are added per-feature during Phase 3.
|
*/

// Health check — unauthenticated, DB-free. Confirms the v2 lane is wired and
// that responses are emitted raw (no `data` wrapper).
Route::get('/ping', function () {
    return [
        'status' => 'ok',
        'apiVersion' => 'v2',
        'time' => now()->toIso8601String(),
    ];
})->name('api.v2.ping');

// --- 11.1 Authentication -----------------------------------------------------
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:v2-login');
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/password/force-change', [AuthController::class, 'forcePasswordChange']);
    });
});

// --- 11.2 Profile ------------------------------------------------------------
Route::middleware('auth:api')->prefix('profile')->group(function () {
    Route::get('/me', [ProfileController::class, 'me']);
});

// --- 11.3 Sectors, commitments & deliverables (read hierarchy) ---------------
Route::middleware('auth:api')->group(function () {
    Route::get('/sectors', [SectorController::class, 'index']);
    Route::get('/sectors/{id}', [SectorController::class, 'show']);
    Route::get('/sectors/{id}/commitments', [SectorController::class, 'commitments']);

    Route::get('/commitments/{id}', [CommitmentController::class, 'show']);
    Route::get('/commitments/{id}/deliverables', [CommitmentController::class, 'deliverables']);

    Route::get('/deliverables/{id}', [DeliverableController::class, 'show']);
});

// --- 11.4 KPI tracking -------------------------------------------------------
Route::middleware('auth:api')->group(function () {
    Route::get('/deliverables/{id}/kpis', [KpiController::class, 'index']);
    Route::get('/kpis/{id}', [KpiController::class, 'show']);

    Route::post('/kpis/{id}/submissions', [KpiController::class, 'submit']);
    Route::post('/kpis/{id}/milestones', [KpiController::class, 'setMilestone']);
    Route::post('/kpis/{id}/tracking-entries', [KpiController::class, 'addTracking']);
});

// ---------------------------------------------------------------------------
// Remaining feature groups (approvals, dashboards, reports, …) are added
// per-feature in subsequent Phase 3 steps. System signals use `auth.optional`.
// ---------------------------------------------------------------------------
