<?php

use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\KpiController;
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

//Route::get('/', [AuthLoginController::class, 'showLoginForm']);
Route::get('/', [CommentController::class, 'index'])->name('home');
Route::get('/login', [AuthLoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthLoginController::class, 'login'])->name('login');
Route::get('logout', [AuthLoginController::class, 'logout'])->name('logout');


Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// User Resource
Route::get('users', [UserController::class, 'index'])->name("users.index");
Route::get('delivery/tracking/awaiting/', [UserController::class, 'awaitingVerification'])->name("delivery.awaiting.verification");
Route::get('delivery/tracking/awaiting/comm/{id}/view', [UserController::class, 'awaitingVerificationCommView'])->name("delivery.awaiting.verification.comm.view");
Route::get('delivery/tracking/awaiting/del/{id}/view', [UserController::class, 'awaitingVerificationDelView'])->name("delivery.awaiting.verification.del.view");
Route::get('delivery/tracking/awaiting/{id}/view', [UserController::class, 'awaitingVerificationView'])->name("delivery.awaiting.verification.view");
Route::post('performance/update', [UserController::class, 'updatePerformance'])->name("update.performance");
Route::post('users/store', [UserController::class, 'store'])->name("users.add");
Route::post('users/user/change-password', [UserController::class, 'changePassword'])->name('users.user.change.password');
Route::get('users/view/{user}', [UserController::class, 'view'])->name("users.view");

Route::get('chart/sector/kpi/performance', [ChartController::class, 'kpiPerformance'])->name('chart.sector.kpi.performance');
Route::get('chart/sector/kpi/performance/ratio', [ChartController::class, 'kpiPerformanceRatio'])->name('chart.sector.kpi.performance.ratio');
Route::get('chart/sector/budget/distribution', [ChartController::class, 'budgetDistribution'])->name('chart.sector.budget.distribution');
Route::get('chart/sector/budget/pending', [ChartController::class, 'pendingCompleted'])->name('chart.sector.pending.completed');
// Sector Resource
Route::get('sectors', [SectorController::class, 'index'])->name('sectors.index');
Route::post('sectors/update', [SectorController::class, 'update'])->name('sectors.update');
Route::post('sectors/save', [SectorController::class, 'store'])->name('sectors.save');
Route::post('sectors/documents/save', [SectorController::class, 'storeDoc'])->name('sectors.document.save');
Route::post('sectors/budget/save', [SectorController::class, 'storeBudget'])->name('sectors.budget.save');
Route::get('sectors/show/{id}/', [SectorController::class, 'show'])->name('sectors.show');
Route::get('sectors/budget/', [SectorController::class, 'budget'])->name('sectors.budget');
Route::get('sectors/delete/{sector}', [SectorController::class, 'destroy'])->name('sectors.delete');
Route::get('sectors/{id}/details/{id2?}', [SectorController::class, 'view'])->name('sectors.view');

// Sector Resource
Route::get('commitment', [CommitmentController::class, 'index'])->name('commitments.index');
Route::post('commitment/update', [CommitmentController::class, 'update'])->name('commitments.update');
Route::post('commitment/save', [CommitmentController::class, 'store'])->name('commitments.save');
Route::post('commitment/budget/save', [CommitmentController::class, 'storeBudget'])->name('commitments.budget.save');
Route::any('commitment/deliverables/{commitment}', [CommitmentController::class, 'deliverables'])->name('commitments.deliverables');
Route::get('commitment/{commitment}/delete', [CommitmentController::class,'delete'])->name('commitments.delete');


Route::post('deliverable/save', [DeliverableController::class, 'store'])->name('deliverable.save');
Route::get('deliverable/view', [DeliverableController::class, 'view'])->name('deliverable.view');
//Route::get('deliverable/add/kpi', [DeliverableController::class, 'addKPI'])->name('deliverable.add.kpi');
Route::get('deliverable/kpis/{deliverable}', [DeliverableController::class, 'kpis'])->name('deliverable.kpis');

Route::post('deliverable/add/kpi', [KpiController::class, 'store'])->name('deliverable.add.kpi');
Route::get('commitment/deliverable/kpi/{kpi}/{track}', [KpiController::class, 'tracking'])->name('performance.tracking');
Route::post('deliverable/kpi/store/tracking', [KpiController::class, 'storeTracking'])->name('deliverable.store.tracking');
Route::post('deliverable/kpi/store/del/dept', [KpiController::class, 'storeTracking'])->name('deliverable.store.tracking.del.dept');

Route::get('projects/{commitment}/details', [CommentController::class, 'details'])->name('public.project.details');
Route::post('projects/post/comment', [CommentController::class, 'postComment'])->name('home.post.comment');

