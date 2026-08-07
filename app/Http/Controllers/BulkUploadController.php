<?php

namespace App\Http\Controllers;

use App\Models\DataEntryAccess;
use App\Models\Framework;
use App\Models\Sector;
use App\Models\User;
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

        ['canAccessAllSectors' => $canAccessAllSectors, 'assignedSectorIds' => $assignedSectorIds] = $access;

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

        $sectorEntryAccess = $sectors->mapWithKeys(function ($sector) use ($entryYear, $entryQuarter, $canAccessAllSectors) {
            return [
                $sector->id => $canAccessAllSectors || DataEntryAccess::isDataEntryAllowed($sector->id, $entryYear, $entryQuarter),
            ];
        })->toArray();

        $uploadAllowed = $canAccessAllSectors;

        if (!$uploadAllowed && $defaultSectorId) {
            $uploadAllowed = $sectorEntryAccess[$defaultSectorId] ?? false;
        }

        if (!$uploadAllowed && !empty($assignedSectorIds)) {
            $uploadAllowed = collect($assignedSectorIds)
                ->contains(fn (int $sectorId) => $sectorEntryAccess[$sectorId] ?? false);
        }

        $entryDeadline = null;
        if ($defaultSectorId && !$uploadAllowed) {
            $accessRecord = DataEntryAccess::where('sector_id', $defaultSectorId)
                ->where('year', $entryYear)
                ->where('quarter', $entryQuarter)
                ->first();

            $entryDeadline = $accessRecord
                ? ($accessRecord->override_deadline ?? $accessRecord->deadline_date)
                : DataEntryAccess::calculateDeadline($entryYear, $entryQuarter);
        }

        return view('pages.bulk-upload.index', compact(
            'frameworks',
            'sectorsByFramework',
            'defaultFrameworkId',
            'defaultSectorId',
            'canAccessAllSectors',
            'sectorSelectionLocked',
            'uploadAllowed',
            'sectorEntryAccess',
            'entryYear',
            'entryQuarter',
            'entryDeadline',
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

        ['canAccessAllSectors' => $canAccessAllSectors, 'assignedSectorIds' => $assignedSectorIds] = $access;

        $validated = $request->validate([
            'framework_id' => ['required', 'exists:frameworks,id'],
            'sector_id' => ['required', 'exists:sectors,id'],
            'upload_file' => ['required', 'file', 'mimes:xlsx,csv', 'max:51200'],
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

        if (!$this->isDataEntryAllowed($sector->id)) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'The data entry window is closed for this sector. Please contact the PDCU Coordinator to request an extension.');
        }

        try {
            $preview = $parser->parse($request->file('upload_file'), true);
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
        ];

        session([
            'bulk_upload_preview' => $preview,
            'bulk_upload_meta' => $meta,
        ]);

        return view('pages.bulk-upload.preview', [
            'preview' => $preview,
            'meta' => $meta,
            'canAccessAllSectors' => $canAccessAllSectors,
        ]);
    }

    /**
     * Persist reviewed framework structure from the uploaded template.
     */
    public function submit(Request $request, BulkUploadImporter $importer)
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
        }

        $preview = session('bulk_upload_preview');
        $meta = session('bulk_upload_meta');

        if (!$preview || !$meta) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'Your upload session has expired. Please upload the file again.');
        }

        if (!$this->isDataEntryAllowed($meta['sector_id'])) {
            return redirect()
                ->route('bulk-upload.index')
                ->with('failure', 'The data entry window is closed for this sector.');
        }

        try {
            $importStats = $importer->import($preview, $meta);
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

        $report = BulkUploadReportBuilder::build($preview, $meta, $user, $importStats);

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
    public function downloadTemplate()
    {
        $user = Auth::user();
        $access = $this->authorizeBulkUploadUser($user);
        if ($access instanceof \Illuminate\Http\RedirectResponse) {
            return $access;
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
        if (!$user || !$user->isDeliveryUnit()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        return [
            'canAccessAllSectors' => $user->canAccessAllSectors(),
            'assignedSectorIds' => $user->getAssignedSectorIds(),
        ];
    }
}
