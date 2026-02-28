<?php

use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\ChartController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommitmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataEntryAccessController;
use App\Http\Controllers\DeliverableController;
use App\Http\Controllers\FrameworkController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\PublicGalleryController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\UserController;
use App\Models\PerformanceTracking;
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

Route::get('/', [AuthLoginController::class, 'index'])->name('home');

//Route::get('/', [AuthLoginController::class, 'showLoginForm']);
Route::get('/sec-proj', [CommentController::class, 'index'])->name('home2');
Route::get('/login', [AuthLoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [AuthLoginController::class, 'login'])->name('login.process');
Route::get('logout', [AuthLoginController::class, 'logout'])->name('logout');

// Public Gallery Routes (No authentication required - must be before auth routes)
Route::get('gallery', [PublicGalleryController::class, 'index'])->name('public.gallery.index');
Route::get('gallery/{gallery}', [PublicGalleryController::class, 'show'])->name('public.gallery.show');
Route::post('gallery/{gallery}/comments', [PublicGalleryController::class, 'storeComment'])->name('public.gallery.comments.store');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/statistics', [DashboardController::class, 'statistics'])->name('dashboard.statistics');
    Route::get('/home', [AuthLoginController::class, 'logout'])->name('lg');

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
    Route::post('users/update-photo', [UserController::class, 'uploadPhoto'])->name("users.upload.photo");
    Route::post('users/{user}/role/update', [UserController::class, 'updateRole'])->name("users.role.update");
    Route::post('users/{user}/role/revoke', [UserController::class, 'revokeRole'])->name("users.role.revoke");
    Route::post('users/{user}/role/reactivate', [UserController::class, 'reactivateRole'])->name("users.role.reactivate");

    Route::get('chart/sector/kpi/performance', [ChartController::class, 'kpiPerformance'])->name('chart.sector.kpi.performance');
    Route::get('chart/sector/kpi/performance/ratio', [ChartController::class, 'kpiPerformanceRatio'])->name('chart.sector.kpi.performance.ratio');
    Route::get('chart/sector/budget/distribution', [ChartController::class, 'budgetDistribution'])->name('chart.sector.budget.distribution');
    Route::get('chart/sector/budget/pending', [ChartController::class, 'pendingCompleted'])->name('chart.sector.pending.completed');

    // MDA/Sector Resource
    Route::get('sectors', [SectorController::class, 'index'])->name('sectors.index');
    Route::post('sectors/update', [SectorController::class, 'update'])->name('sectors.update');
    Route::post('sectors/save', [SectorController::class, 'store'])->name('sectors.save');
    Route::post('sectors/documents/save', [SectorController::class, 'storeDoc'])->name('sectors.document.save');
    Route::post('sectors/budget/save', [SectorController::class, 'storeBudget'])->name('sectors.budget.save');
    Route::get('sectors/show/{id}/', [SectorController::class, 'show'])->name('sectors.show');
    Route::get('sectors/budget/', [SectorController::class, 'budget'])->name('sectors.budget');
    Route::get('sectors/delete/{sector}', [SectorController::class, 'destroy'])->name('sectors.delete');
    Route::get('sectors/{id}/details/{id2?}', [SectorController::class, 'view'])->name('sectors.view');

    // MDA/Sector Resource
    Route::get('commitment', [CommitmentController::class, 'index'])->name('commitments.index');
    Route::post('commitment/update', [CommitmentController::class, 'update'])->name('commitments.update');
    Route::post('commitment/save', [CommitmentController::class, 'store'])->name('commitments.save');
    Route::post('commitment/change/photo', [CommitmentController::class, 'changePhoto'])->name('commitments.change.photo');
    Route::post('commitment/budget/save', [CommitmentController::class, 'storeBudget'])->name('commitments.budget.save');
    Route::any('commitment/deliverables/{commitment}', [CommitmentController::class, 'deliverables'])->name('commitments.deliverables');
    Route::get('commitment/{commitment}/delete', [CommitmentController::class, 'delete'])->name('commitments.delete');


    Route::post('deliverable/kpi/tracking/save', [DeliverableController::class, 'storeTracking'])->name('deliverable.tracking.save');
    Route::post('deliverable/save', [DeliverableController::class, 'store'])->name('deliverable.save');
    Route::post('deliverable/update', [DeliverableController::class, 'update'])->name('deliverable.update');
    Route::get('deliverable/view', [DeliverableController::class, 'view'])->name('deliverable.view');
    Route::get('deliverables/{deliverable}/delete', [DeliverableController::class, 'delete'])->name('deliverables.delete');
    //Route::get('deliverable/add/kpi', [DeliverableController::class, 'addKPI'])->name('deliverable.add.kpi');
    Route::get('deliverable/kpis/{deliverable}', [DeliverableController::class, 'kpis'])->name('deliverable.kpis');

    Route::post('deliverable/add/kpi', [KpiController::class, 'store'])->name('deliverable.add.kpi');
    Route::post('kpi/update', [KpiController::class, 'update'])->name('kpi.update');
    Route::get('commitment/deliverable/kpi/{kpi}/{track}', [KpiController::class, 'tracking'])->name('performance.tracking');
    Route::post('deliverable/kpi/store/tracking', [KpiController::class, 'storeTracking'])->name('deliverable.store.tracking');
    Route::get('deliverable/kpi/tracking/files/{id}', [PerformanceTracking::class, 'attachments'])->name('deliverable.kpi.tracking.files');
    Route::post('deliverable/kpi/store/del/dept', [KpiController::class, 'storeTracking'])->name('deliverable.store.tracking.del.dept');
    Route::post('deliverable/kpi/target/save', [KpiController::class, 'saveTarget'])->name('kpis.target.save');
    Route::get('deliverable/kpi/{kpi}/delete', [KpiController::class, 'delete'])->name('kpis.delete');
    Route::post('performance-tracking/approve', [KpiController::class, 'approveData'])->name('performance.tracking.approve');
    Route::post('performance-tracking/facilitator-confirm', [KpiController::class, 'facilitatorConfirm'])->name('performance.tracking.facilitator.confirm');
    Route::post('performance-tracking/coordinator-confirm', [KpiController::class, 'coordinatorConfirm'])->name('performance.tracking.coordinator.confirm');

    //  REPORTS
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('reports/generate', [ReportController::class, 'generate'])->name('reports.generate');
    Route::post('/reports/download', [ReportController::class, 'download'])->name('reports.download');

    // Comprehensive KPI Report
    Route::get('reports/comprehensive', [ReportController::class, 'comprehensiveReport'])->name('reports.comprehensive');
    Route::post('reports/comprehensive/download', [ReportController::class, 'downloadComprehensiveReport'])->name('reports.comprehensive.download');

    // Word Document Report
    Route::get('reports/word', [ReportController::class, 'wordReportForm'])->name('reports.word.form');
    Route::post('reports/word/generate', [ReportController::class, 'generateWordReport'])->name('reports.word.generate');
    Route::post('reports/comprehensive/print', [ReportController::class, 'printComprehensiveReport'])->name('reports.comprehensive.print');

    // Gallery Management (Admin only)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('gallery', GalleryController::class);
    });

    // Data Entry Access Management (PDCU Coordinators only)
    Route::prefix('data-entry')->name('data-entry.')->group(function () {
        Route::get('/', [DataEntryAccessController::class, 'index'])->name('index');
        Route::post('/grant-override', [DataEntryAccessController::class, 'grantOverride'])->name('grant-override');
        Route::post('/lock-all', [DataEntryAccessController::class, 'lockAll'])->name('lock-all');
        Route::post('/unlock-all', [DataEntryAccessController::class, 'unlockAll'])->name('unlock-all');
        Route::post('/initialize-quarter', [DataEntryAccessController::class, 'initializeQuarter'])->name('initialize-quarter');
    });

    // Framework Management (PDCU Coordinators only)
    Route::prefix('frameworks')->name('frameworks.')->group(function () {
        Route::get('/', [FrameworkController::class, 'index'])->name('index');
        Route::get('/create', [FrameworkController::class, 'create'])->name('create');
        // Specific routes must come before parameterized routes
        Route::post('/confirm-inherit', [FrameworkController::class, 'confirmInherit'])->name('confirm-inherit');
        Route::post('/', [FrameworkController::class, 'store'])->name('store');
        // Parameterized routes come last
        Route::get('/{framework}', [FrameworkController::class, 'show'])->name('show');
        Route::post('/{framework}/archive', [FrameworkController::class, 'archive'])->name('archive');
        Route::post('/{framework}/activate', [FrameworkController::class, 'activate'])->name('activate');
    });
});

Route::get('mdas/{commitment}/details', [CommentController::class, 'mda'])->name('public.mda.details');
Route::get('projects/{commitment}/details', [CommentController::class, 'details'])->name('public.project.details');
Route::post('projects/post/comment', [CommentController::class, 'postComment'])->name('home.post.comment');
