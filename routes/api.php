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
Route::get('/project/comment/add', [ProjectController::class, 'addComment']);

Route::middleware('auth:api')->prefix('/')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::prefix('/projects')->group(function () {
        Route::get('/u', [ProjectController::class, 'index']);
    });
});
