<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/project/{id}', [ProjectController::class, 'project']);
Route::post('/project/comment/add', [ProjectController::class, 'addComment']);

Route::middleware('auth:api')->prefix('/')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::prefix('/sectors')->group(function () {
        Route::get('/', [ProjectController::class, 'sectors']);
    });
    Route::prefix('/project')->group(function () {
        Route::get('/commitments/{sector_id}', [ProjectController::class, 'commitments']);
        Route::get('/deliverables/{commitment_id?}', [ProjectController::class, 'deliverables']);
        Route::get('/kpis/{deliverable_id?}', [ProjectController::class, 'getKPIs']);

        Route::post('/insert/deliverable', [ProjectController::class, 'insertDeliverable']);
        Route::post('/insert/commitment', [ProjectController::class, 'insertCommitment']);
        Route::post('/insert/kpi', [ProjectController::class, 'insertKpi']);
        Route::post('/set/kpi/target', [ProjectController::class, 'setTarget']);
    });
    Route::prefix('/report')->group(function () {
        Route::get('/', [ProjectController::class, 'report']);
    });
    Route::prefix('/user')->group(function () {
        Route::post('/edit', [ProjectController::class, 'editUser']);
        Route::get('/fetch', [ProjectController::class, 'getUser']);
        Route::post('/change/password', [ProjectController::class, 'changePassword']);

        Route::get('/notifications', [ProjectController::class, 'notifications']);
        Route::post('/notification/mark/read', [ProjectController::class, 'markRead']);
        Route::post('/save/fcm-token', [ProjectController::class, 'saveFcmToken']);
    });
});
