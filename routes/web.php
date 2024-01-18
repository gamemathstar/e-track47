<?php

use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

//Route::get('/',[AuthLoginController::class, 'showLoginForm']);

Route::get('/', [AuthLoginController::class, 'showLoginForm']);
Route::get('/login', [AuthLoginController::class, 'showLoginForm']);
Route::post('login', [AuthLoginController::class, 'login'])->name('login');
Route::get('logout', [AuthLoginController::class, 'logout'])->name('logout');


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Resource
Route::get('users', [UserController::class,'index'])->name("users.index");
Route::post('users/store', [UserController::class,'store'])->name("users.add");
Route::get('users/view/{id}', [UserController::class,'view'])->name("users.view");

// Sector Resource
Route::get('sectors', [SectorController::class,'index'])->name('sectors.index');
Route::post('sectors/update', [SectorController::class,'update'])->name('sectors.update');
Route::post('sectors/save', [SectorController::class,'store'])->name('sectors.save');
Route::post('sectors/documents/save', [SectorController::class,'storeDoc'])->name('sectors.document.save');
Route::post('sectors/budget/save', [SectorController::class,'storeBudget'])->name('sectors.budget.save');
Route::get('sectors/show/{id}/', [SectorController::class,'show'])->name('sectors.show');
Route::get('sectors/budget/', [SectorController::class,'budget'])->name('sectors.budget');
Route::get('sectors/{id}/{id2?}', [SectorController::class,'view'])->name('sectors.view');

// Sector Resource
Route::get('commitment', [CommitmentController::class,'index'])->name('commitments.index');
Route::post('commitment/update', [CommitmentController::class,'update'])->name('commitments.update');
Route::post('commitment/save', [CommitmentController::class,'store'])->name('commitments.save');
Route::post('commitment/budget/save', [CommitmentController::class,'storeBudget'])->name('commitments.budget.save');
Route::any('commitment/deliverables/{id}', [CommitmentController::class,'deliverables'])->name('commitments.deliverables');
//Route::get('commitment/{id}/{year?}', [CommitmentController::class,'show'])->name('commitments.view');


Route::post('deliverable/save', [DeliverableController::class,'store'])->name('deliverable.save');
Route::get('deliverable/view', [DeliverableController::class,'view'])->name('deliverable.view');
Route::get('deliverable/add/kpi', [DeliverableController::class,'addKPI'])->name('deliverable.add.kpi');
