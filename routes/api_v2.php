<?php

use App\Http\Controllers\Api\V2\ApprovalController;
use App\Http\Controllers\Api\V2\AuthController;
use App\Http\Controllers\Api\V2\CommitmentController;
use App\Http\Controllers\Api\V2\DashboardController;
use App\Http\Controllers\Api\V2\DataEntryWindowController;
use App\Http\Controllers\Api\V2\DeliverableController;
use App\Http\Controllers\Api\V2\DiscussionsController;
use App\Http\Controllers\Api\V2\FrameworkController;
use App\Http\Controllers\Api\V2\GalleryController;
use App\Http\Controllers\Api\V2\HealthController;
use App\Http\Controllers\Api\V2\KpiController;
use App\Http\Controllers\Api\V2\NotificationsController;
use App\Http\Controllers\Api\V2\ProfileController;
use App\Http\Controllers\Api\V2\ReportsController;
use App\Http\Controllers\Api\V2\SectorController;
use App\Http\Controllers\Api\V2\SettingsController;
use App\Http\Controllers\Api\V2\SystemController;
use App\Http\Controllers\Api\V2\UsersController;
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

// Deploy / CI readiness probe — unauthenticated. Reports 200 when the v2 lane
// can issue + verify tokens (Passport keys + oauth client present, DB reachable),
// or 503 with diagnostics when it can't. Safe to expose to the mobile team and
// load balancers — non-sensitive metadata only.
Route::get('/_health', [HealthController::class, 'show'])->name('api.v2.health');

// Liveness check — unauthenticated, DB-free. Confirms the v2 lane is wired and
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

    // §11.4.6 — annual targets per deliverable
    Route::get('/deliverables/{id}/annual-targets', [DeliverableController::class, 'annualTargets']);
    Route::post('/deliverables/{id}/annual-targets', [DeliverableController::class, 'saveAnnualTargets']);

    Route::post('/kpis/{id}/submissions', [KpiController::class, 'submit']);
    Route::get('/kpis/{id}/milestones', [KpiController::class, 'getMilestone']);
    Route::post('/kpis/{id}/milestones', [KpiController::class, 'setMilestone']);
    Route::get('/kpis/{id}/tracking-context', [KpiController::class, 'trackingContext']);
    Route::post('/kpis/{id}/tracking-entries', [KpiController::class, 'addTracking']);
    Route::post('/kpis/{id}/evidence', [KpiController::class, 'uploadEvidence']);
    Route::delete('/kpis/{id}/evidence/{docId}', [KpiController::class, 'deleteEvidence']);
});

// --- 11.6 Approvals workflow -------------------------------------------------
Route::middleware('auth:api')->prefix('approvals')->group(function () {
    Route::get('/coordinator/queue', [ApprovalController::class, 'coordinatorQueue']);
    Route::get('/sector-head/queue', [ApprovalController::class, 'sectorHeadQueue']);
    Route::get('/sector-head/bulk', [ApprovalController::class, 'sectorHeadBulk']);
    Route::get('/facilitator/queue', [ApprovalController::class, 'facilitatorQueue']);
    Route::get('/data-admin/my-kpis', [ApprovalController::class, 'myKpis']);

    Route::post('/submissions/bulk-approve', [ApprovalController::class, 'bulkApprove']);
    Route::get('/submissions/{kpiId}', [ApprovalController::class, 'submissionDetail']);
    Route::post('/submissions/{submissionId}/review', [ApprovalController::class, 'review']);
});

// --- 11.11 Dashboards (role snapshots) ---------------------------------------
Route::middleware('auth:api')->prefix('dashboard')->group(function () {
    Route::get('/governor', [DashboardController::class, 'governor']);
    Route::get('/coordinator', [DashboardController::class, 'coordinator']);
    Route::get('/facilitator', [DashboardController::class, 'facilitator']);
    Route::get('/sector-head', [DashboardController::class, 'sectorHead']);
    Route::get('/data-admin', [DashboardController::class, 'dataAdmin']);
    Route::get('/system-admin', [DashboardController::class, 'systemAdmin']);
});

// --- 11.7 Data-entry windows (Coordinator) -----------------------------------
Route::middleware('auth:api')->prefix('data-entry')->group(function () {
    Route::get('/windows', [DataEntryWindowController::class, 'index']);
    Route::get('/stats', [DataEntryWindowController::class, 'stats']);
    Route::post('/windows/lock-all', [DataEntryWindowController::class, 'lockAll']);
    Route::post('/windows/unlock-all', [DataEntryWindowController::class, 'unlockAll']);
    Route::post('/windows/{sectorId}/open', [DataEntryWindowController::class, 'open']);
    Route::post('/windows/{sectorId}/lock', [DataEntryWindowController::class, 'lock']);
    Route::post('/windows/{sectorId}/override', [DataEntryWindowController::class, 'override']);
});

// --- 11.5 Frameworks ---------------------------------------------------------
Route::middleware('auth:api')->prefix('frameworks')->group(function () {
    Route::get('/', [FrameworkController::class, 'index']);
    Route::get('/stats', [FrameworkController::class, 'stats']);
    Route::get('/{id}', [FrameworkController::class, 'show']);
    Route::get('/{id}/sectors', [FrameworkController::class, 'sectors']);
    Route::post('/', [FrameworkController::class, 'store']);
    Route::post('/{id}/archive', [FrameworkController::class, 'archive']);
    Route::post('/{id}/set-default', [FrameworkController::class, 'setDefault']);
});

// --- 11.9 Users & security ---------------------------------------------------
Route::middleware('auth:api')->group(function () {
    Route::get('/users', [UsersController::class, 'index']);
    Route::get('/users/security-log', [UsersController::class, 'securityLog']);
    Route::post('/users/me/password', [UsersController::class, 'changeMyPassword']);
    Route::post('/users/me/photo', [UsersController::class, 'updateMyPhoto']);
    Route::post('/users', [UsersController::class, 'store']);
    Route::get('/users/{id}', [UsersController::class, 'show']);
});

// --- 11.13 Gallery -----------------------------------------------------------
// Public-capable reads: /public and item detail are accessible to signed-out
// callers (no Authorization header required). When a bearer IS sent, the
// auth.optional middleware still populates the user — so list/detail can
// surface a "you liked this" badge or similar without a separate endpoint.
// Management list + upload remain auth-required.
Route::middleware('auth.optional')->prefix('gallery')->group(function () {
    Route::get('/public', [GalleryController::class, 'publicList']);
    Route::get('/items/{id}', [GalleryController::class, 'show']);
    // Public comment submission — held for moderation, never echoed.
    // Throttled per-IP since unauthenticated. 10/min is enough for a real
    // user but cuts off scripted spam quickly.
    Route::post('/items/{id}/comments', [GalleryController::class, 'submitComment'])
        ->middleware('throttle:10,1');
});
Route::middleware('auth:api')->prefix('gallery')->group(function () {
    Route::get('/management', [GalleryController::class, 'management']);
    Route::post('/items', [GalleryController::class, 'upload']);
});

// --- 11.14 Notifications -----------------------------------------------------
Route::middleware('auth:api')->prefix('notifications')->group(function () {
    Route::get('/inbox', [NotificationsController::class, 'inbox']);
    Route::get('/preferences', [NotificationsController::class, 'preferences']);
    Route::put('/preferences', [NotificationsController::class, 'updatePreferences']);
    Route::post('/mark-all-read', [NotificationsController::class, 'markAllRead']);
    Route::post('/{id}/mark-read', [NotificationsController::class, 'markRead']);
    Route::post('/device-token', [NotificationsController::class, 'registerDeviceToken']);
    Route::delete('/device-token', [NotificationsController::class, 'unregisterDeviceToken']);
});

// --- 11.12 Settings / Help / About -------------------------------------------
Route::middleware('auth:api')->prefix('settings')->group(function () {
    Route::get('/preferences', [SettingsController::class, 'preferences']);
    Route::put('/preferences', [SettingsController::class, 'updatePreferences']);
    Route::post('/clear-cache', [SettingsController::class, 'clearCache']);
    Route::post('/sync', [SettingsController::class, 'sync']);
    Route::post('/sign-out-all', [SettingsController::class, 'signOutAll']);
    Route::get('/faqs', [SettingsController::class, 'faqs']);
    Route::post('/feedback', [SettingsController::class, 'feedback']);
    Route::get('/about', [SettingsController::class, 'about']);
});

// --- 11.8 Reports ------------------------------------------------------------
Route::middleware('auth:api')->prefix('reports')->group(function () {
    Route::get('/hub', [ReportsController::class, 'hub']);
    Route::post('/setup-preview', [ReportsController::class, 'setupPreview']);
    Route::post('/viewer', [ReportsController::class, 'viewer']);
    Route::post('/comprehensive', [ReportsController::class, 'comprehensive']);
    Route::post('/comprehensive-report', [ReportsController::class, 'comprehensiveReport']);
    Route::post('/word', [ReportsController::class, 'word']);
    Route::post('/word-document', [ReportsController::class, 'wordDocument']);
    Route::get('/print-preview', [ReportsController::class, 'printPreview']);
});

// --- 11.10 System signals ----------------------------------------------------
// status / update / onboarding-slides are public-capable (auth.optional) per A6.
Route::middleware('auth.optional')->prefix('system')->group(function () {
    Route::get('/status', [SystemController::class, 'status']);
    Route::get('/update', [SystemController::class, 'update']);
    Route::get('/onboarding', [SystemController::class, 'onboardingSlides']);
});
Route::middleware('auth:api')->prefix('system')->group(function () {
    Route::get('/offline-snapshot', [SystemController::class, 'offlineSnapshot']);
    Route::post('/retry', [SystemController::class, 'retry']);
    Route::post('/onboarding/complete', [SystemController::class, 'completeOnboarding']);
});

// --- 11.15 Discussions -------------------------------------------------------
Route::middleware('auth:api')->prefix('discussions')->group(function () {
    Route::get('/hub', [DiscussionsController::class, 'hub']);
    Route::get('/sectors/{sectorId}/threads', [DiscussionsController::class, 'sectorThreads']);
    Route::get('/threads/{threadId}', [DiscussionsController::class, 'threadDetail']);
    Route::post('/threads/{threadId}/comments', [DiscussionsController::class, 'postComment']);
    Route::post('/comments/{commentId}/toggle-like', [DiscussionsController::class, 'toggleLike']);
});

// ---------------------------------------------------------------------------
// Remaining feature groups (reports, users, gallery, notifications, settings,
// discussions, system) are added per-feature in subsequent Phase 3 steps.
// System signals use the `auth.optional` alias.
// ---------------------------------------------------------------------------
