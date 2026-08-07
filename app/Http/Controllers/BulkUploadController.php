<?php

namespace App\Http\Controllers;

use App\Models\DataEntryAccess;
use App\Models\Framework;
use App\Models\Sector;
use App\Models\User;
use App\Services\BulkUploadActualsEnricher;
use App\Services\BulkUploadActualsImporter;
use App\Services\BulkUploadActualsTemplateExporter;
use App\Services\BulkUploadEntryAccess;
use App\Services\BulkUploadImporter;
use App\Services\BulkUploadParser;
use App\Services\BulkUploadReportBuilder;
use App\Traits\ChecksDataEntryAccess;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BulkUploadController extends Controller
{
    use ChecksDataEntryAccess;

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display the bulk upload page.
     */
    public function index()
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        ['canAccessAllSectors' => $canAccessAllSectors, 'assignedSectorIds' => $assignedSectorIds, 'uploadMode' => $uploadMode] = $access;

        $frameworks = Framework::query()
            ->orderByDesc('year')
            ->get(['id', 'year', 'status']);

        if ($canAccessAllSectors) {
            $sectors = Sector::query()
                ->select('id', 'sector_name', 'framework_id')
                ->whereNotNull('framework_id')
                ->orderBy('sector_name')
                ->get();
        } else {
            $sectors = Sector::query()
                ->select('id', 'sector_name', 'framework_id')
                ->whereIn('id', $assignedSectorIds)
                ->orderBy('sector_name')
                ->get();

            $frameworkIds = $sectors->pluck('framework_id')->filter()->unique();
            $frameworks = $frameworks->whereIn('id', $frameworkIds)->values();
        }

        $sectorsByFramework = $sectors
            ->groupBy('framework_id')
            ->map(fn ($items) => $items->map(fn ($sector) => [
                'id' => $sector->id,
                'name' => $sector->sector_name,
            ])->values())
            ->toArray();

        $defaultFrameworkId = $frameworks->firstWhere('status', 'Active')?->id
            ?? $frameworks->first()?->id;

        $defaultSectorId = !$canAccessAllSectors && count($assignedSectorIds) === 1
            ? $assignedSectorIds[0]
            : null;

        $sectorSelectionLocked = !$canAccessAllSectors && count($assignedSectorIds) === 1;

        $entryYear = DataEntryAccess::getCurrentYear();
        $entryQuarter = DataEntryAccess::getCurrentQuarter();

        $sectorQuarterEntryAccess = BulkUploadEntryAccess::sectorQuarterAccessMap(
            $sectors,
            $frameworks,
            $canAccessAllSectors,
        );

        $sectorEntryAccess = $sectors->mapWithKeys(function ($sector) use ($entryYear, $entryQuarter, $canAccessAllSectors, $uploadMode, $sectorQuarterEntryAccess, $frameworks) {
            if ($uploadMode === 'actuals') {
                $frameworkYear = (int) ($frameworks->firstWhere('id', $sector->framework_id)?->year ?? $entryYear);

                return [
                    $sector->id => collect(range(1, 4))
                        ->contains(fn (int $quarter) => $sectorQuarterEntryAccess[$sector->id][$frameworkYear][$quarter] ?? false),
                ];
            }

            return [
                $sector->id => $canAccessAllSectors || DataEntryAccess::isDataEntryAllowed($sector->id, $entryYear, $entryQuarter),
            ];
        })->toArray();

        $defaultReportingQuarter = $uploadMode === 'actuals' ? $entryQuarter : null;

        $uploadAllowed = $canAccessAllSectors;

        if (!$uploadAllowed && $defaultSectorId) {
            if ($uploadMode === 'actuals') {
                $frameworkYear = (int) ($frameworks->firstWhere('id', $sectors->firstWhere('id', $defaultSectorId)?->framework_id)?->year ?? $entryYear);
                $uploadAllowed = $sectorQuarterEntryAccess[$defaultSectorId][$frameworkYear][$defaultReportingQuarter] ?? false;
            } else {
                $uploadAllowed = $sectorEntryAccess[$defaultSectorId] ?? false;
            }
        }

        if (!$uploadAllowed && !empty($assignedSectorIds) && $uploadMode !== 'actuals') {
            $uploadAllowed = collect($assignedSectorIds)
                ->contains(fn (int $sectorId) => $sectorEntryAccess[$sectorId] ?? false);
        }

        $entryDeadline = null;
        if ($defaultSectorId && !$uploadAllowed) {
            $deadlineYear = $uploadMode === 'actuals'
                ? (int) ($frameworks->firstWhere('id', $sectors->firstWhere('id', $defaultSectorId)?->framework_id)?->year ?? $entryYear)
                : $entryYear;
            $deadlineQuarter = $uploadMode === 'actuals' ? $defaultReportingQuarter : $entryQuarter;

            $accessRecord = DataEntryAccess::where('sector_id', $defaultSectorId)
                ->where('year', $deadlineYear)
                ->where('quarter', $deadlineQuarter)
                ->first();

            $entryDeadline = $accessRecord
                ? ($accessRecord->override_deadline ?? $accessRecord->deadline_date)
                : DataEntryAccess::calculateDeadline($deadlineYear, $deadlineQuarter);
        }

        $frameworkYears = $frameworks->pluck('year', 'id')->toArray();

        return view('pages.bulk-upload.index', compact(
            'frameworks',
            'sectorsByFramework',
            'defaultFrameworkId',
            'defaultSectorId',
            'canAccessAllSectors',
            'sectorSelectionLocked',
            'uploadAllowed',
            'sectorEntryAccess',
            'sectorQuarterEntryAccess',
            'frameworkYears',
            'entryYear',
            'entryQuarter',
            'entryDeadline',
            'uploadMode',
            'defaultReportingQuarter',
        ));
    }

    /**
     * Validate and preview uploaded performance data.
     */
    public function preview(Request $request, BulkUploadParser $parser)
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        ['canAccessAllSectors' => $canAccessAllSectors, 'assignedSectorIds' => $assignedSectorIds, 'uploadMode' => $uploadMode] = $access;

        $validated = $request->validate([
            'framework_id' => ['required', 'exists:frameworks,id'],
            'sector_id' => ['required', 'exists:sectors,id'],
            'upload_file' => ['required', 'file', 'mimes:xlsx,csv', 'max:51200'],
            'reporting_quarter' => [$uploadMode === 'actuals' ? 'required' : 'nullable', 'integer', 'in:1,2,3,4'],
        ]);

        $framework = Framework::findOrFail($validated['framework_id']);
        $sector = Sector::findOrFail($validated['sector_id']);

        if ((int) $sector->framework_id !== (int) $framework->id) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'The selected sector does not belong to the chosen fiscal year.');
        }

        if (!$canAccessAllSectors && !in_array((int) $sector->id, $assignedSectorIds, true)) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'You can only upload data for your assigned sector(s).');
        }

        if ($uploadMode === 'actuals') {
            $entryError = BulkUploadEntryAccess::validateActualsEntry(
                (int) $sector->id,
                (int) $framework->year,
                isset($validated['reporting_quarter']) ? (int) $validated['reporting_quarter'] : null,
                [],
                $canAccessAllSectors,
            );

            if ($entryError) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', $entryError);
            }
        } elseif (!$this->isDataEntryAllowed($sector->id)) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'The data entry window is closed for this sector. Please contact the PDCU Coordinator to request an extension.');
        }

        $forPdcu = $uploadMode === 'structure';

        try {
            $preview = $parser->parse($request->file('upload_file'), $forPdcu);
        } catch (\Throwable $exception) {
            Log::error('Bulk upload preview failed', ['message' => $exception->getMessage()]);

            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'Unable to read the uploaded file. Please confirm it matches the official template and try again.');
        }

        if (($preview['summary']['total_records'] ?? 0) === 0) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'No performance records were found in the uploaded file.');
        }

        $meta = [
            'framework_id' => $framework->id,
            'framework_year' => $framework->year,
            'sector_id' => $sector->id,
            'sector_name' => $sector->sector_name,
            'file_name' => $request->file('upload_file')->getClientOriginalName(),
            'upload_mode' => $uploadMode,
            'reporting_quarter' => $validated['reporting_quarter'] ?? null,
        ];

        if ($uploadMode === 'actuals') {
            $preview = app(BulkUploadActualsEnricher::class)->enrich($preview, $meta);

            $entryError = BulkUploadEntryAccess::validateActualsEntry(
                (int) $sector->id,
                (int) $framework->year,
                isset($meta['reporting_quarter']) ? (int) $meta['reporting_quarter'] : null,
                $preview,
                $canAccessAllSectors,
            );

            if ($entryError) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', $entryError);
            }

            if (($preview['summary']['actual_updates'] ?? 0) === 0) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', 'No quarterly actual values are ready to submit. Review validation warnings and confirm the selected reporting quarter.');
            }
        }

        session([
            'bulk_upload_preview' => $preview,
            'bulk_upload_meta' => $meta,
        ]);

        return view('pages.bulk-upload.preview', [
            'preview' => $preview,
            'meta' => $meta,
            'canAccessAllSectors' => $canAccessAllSectors,
            'uploadMode' => $uploadMode,
        ]);
    }

    /**
     * Persist reviewed framework structure from the uploaded template.
     */
    public function submit(
        Request $request,
        BulkUploadImporter $structureImporter,
        BulkUploadActualsImporter $actualsImporter,
    ) {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        $uploadMode = $access['uploadMode'];

        $preview = session('bulk_upload_preview');
        $meta = session('bulk_upload_meta');

        if (!$preview || !$meta) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'Your upload session has expired. Please upload the file again.');
        }

        if ($uploadMode === 'actuals') {
            $framework = Framework::findOrFail($meta['framework_id']);
            $entryError = BulkUploadEntryAccess::validateActualsEntry(
                (int) $meta['sector_id'],
                (int) $framework->year,
                isset($meta['reporting_quarter']) ? (int) $meta['reporting_quarter'] : null,
                $preview,
                $access['canAccessAllSectors'],
            );

            if ($entryError) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', $entryError);
            }

            if (($preview['summary']['actual_updates'] ?? 0) === 0) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', 'No quarterly actual values are ready to submit. Please upload the file again.');
            }
        } elseif (!$this->isDataEntryAllowed($meta['sector_id'])) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'The data entry window is closed for this sector.');
        }

        try {
            $importStats = $uploadMode === 'actuals'
                ? $actualsImporter->import($preview, $meta)
                : $structureImporter->import($preview, $meta);
        } catch (\Throwable $exception) {
            Log::error('Bulk upload import failed', [
                'message' => $exception->getMessage(),
                'sector_id' => $meta['sector_id'] ?? null,
                'framework_id' => $meta['framework_id'] ?? null,
            ]);

            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'Unable to save the uploaded records. Please try again or contact support if the problem persists.');
        }

        $report = BulkUploadReportBuilder::build($preview, $meta, $user, $importStats, $uploadMode);

        session([
            'bulk_upload_report' => $report,
        ]);

        session()->forget(['bulk_upload_preview', 'bulk_upload_meta']);

        return redirect()->route('bulk-upload.report');
    }

    /**
     * Display the post-submission report.
     */
    public function report()
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        $report = session('bulk_upload_report');

        if (!$report) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'No submission report is available. Please complete an upload first.');
        }

        $report['submitted_at'] = Carbon::parse($report['submitted_at']);
        $report['audit_trail'] = collect($report['audit_trail'] ?? [])
            ->map(function ($event) {
                $event['timestamp'] = Carbon::parse($event['timestamp']);

                return $event;
            })
            ->all();

        $dashboardRoute = $user->isGovernor()
            ? route('dashboard.statistics')
            : route('dashboard');

        return view('pages.bulk-upload.report', compact('report', 'dashboardRoute'));
    }

    /**
     * Download the bulk performance upload Excel template.
     */
    public function downloadTemplate(Request $request, BulkUploadActualsTemplateExporter $actualsExporter)
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        if ($access['uploadMode'] === 'actuals') {
            $validated = $request->validate([
                'framework_id' => ['required', 'exists:frameworks,id'],
                'sector_id' => ['required', 'exists:sectors,id'],
            ]);

            $framework = Framework::findOrFail($validated['framework_id']);
            $sector = Sector::findOrFail($validated['sector_id']);

            if ((int) $sector->framework_id !== (int) $framework->id) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', 'The selected sector does not belong to the chosen fiscal year.');
            }

            if (!in_array((int) $sector->id, $access['assignedSectorIds'], true)) {
                return redirect()
                    ->route('bulk-upload.index')
                    ->with('failure', 'You can only download templates for your assigned sector.');
            }

            return $actualsExporter->download($sector, $framework);
        }

        $templatePath = resource_path('templates/bulk-performance-upload-template.xlsx');

        if (!is_file($templatePath)) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'Upload template is not available. Please contact the system administrator.');
        }

        return response()->download(
            $templatePath,
            'bulk-performance-upload-template.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function authorizeBulkUploadUser(?User $user): array|\Illuminate\Http\RedirectResponse
    {
        if (!$user || (!$user->isDeliveryUnit() && !$user->isDataAdmin())) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        $assignedSector = $user->isDataAdmin();
        $assignedSectorIds = $assignedSector
            ? [(int) $assignedSector->id]
            : $user->getAssignedSectorIds();

        return [
            'uploadMode' => $assignedSector ? 'actuals' : 'structure',
            'isDataAdmin' => (bool) $assignedSector,
            'canAccessAllSectors' => $user->canAccessAllSectors(),
            'assignedSectorIds' => $assignedSectorIds,
        ];
    }
}
