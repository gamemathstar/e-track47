<?php

namespace App\Http\Controllers;

use App\Models\Framework;
use App\Models\Sector;
use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\Kpi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FrameworkController extends Controller
{
    public function __construct()
    {
        $this->middleware("auth");
    }

    /**
     * Display the framework management page
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Only Coordinators can access this page
        if (!$user->isCoordinator()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        $filter = $request->input('filter', 'all'); // all, active, archived

        $query = Framework::with(['creator', 'archiver'])->orderBy('year', 'desc');

        if ($filter === 'active') {
            $query->where('status', 'Active');
        } elseif ($filter === 'archived') {
            $query->where('status', 'Archived');
        }

        $frameworks = $query->paginate(15);

        // Calculate stats
        $activeCount = Framework::where('status', 'Active')->count();
        $archivedCount = Framework::where('status', 'Archived')->count();

        // Calculate average completion (placeholder - you can implement actual calculation)
        $avgCompletion = 92; // This would be calculated based on actual data

        // Get current active framework year
        $currentActiveFramework = Framework::where('status', 'Active')->first();
        $currentYear = $currentActiveFramework ? $currentActiveFramework->year : null;

        return view('pages.coordinator.frameworks', compact('frameworks', 'activeCount', 'archivedCount', 'avgCompletion', 'currentYear', 'filter'));
    }

    /**
     * Show the form for creating a new framework
     */
    public function create()
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        // Get all existing frameworks (excluding drafts) ordered by year descending
        // Users can inherit from any framework (Active or Archived)
        $existingFrameworks = Framework::whereIn('status', ['Active', 'Archived'])
            ->orderBy('year', 'desc')
            ->get();

        return view('pages.coordinator.framework-create', compact('existingFrameworks'));
    }

    /**
     * Show confirmation page for inheriting framework
     */
    public function confirmInherit(Request $request)
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:frameworks,year',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_framework_id' => 'required|exists:frameworks,id',
        ]);

        $sourceFramework = Framework::with(['creator'])->findOrFail($validated['source_framework_id']);

        // Allow inheriting from both Active and Archived frameworks
        // Only prevent inheriting from Draft frameworks
        if ($sourceFramework->status === 'Draft') {
            return redirect()->back()
                ->with('failure', 'You can only inherit from Active or Archived frameworks.')
                ->withInput();
        }

        // Count items to be copied (all sectors - user will select which ones)
        $sectorsCount = Sector::where('framework_id', $sourceFramework->id)->count();
        $commitmentsCount = Commitment::where('framework_id', $sourceFramework->id)->count();
        $deliverablesCount = Deliverable::where('framework_id', $sourceFramework->id)->count();
        $kpisCount = Kpi::where('framework_id', $sourceFramework->id)->count();

        // Load all sectors with their commitments for structure preview
        $frameworkId = $sourceFramework->id;
        $sectors = Sector::where('framework_id', $frameworkId)
            ->with(['commitments' => function ($query) use ($frameworkId) {
                $query->where('framework_id', $frameworkId);
            }])
            ->orderBy('sector_name')
            ->get();

        return view('pages.coordinator.framework-confirm-inherit', compact('sourceFramework', 'validated', 'sectorsCount', 'commitmentsCount', 'deliverablesCount', 'kpisCount', 'sectors'));
    }

    /**
     * Store a newly created framework
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'You do not have permission to perform this action.');
        }

        $creationMethod = $request->input('creation_method', 'scratch');

        $validated = $request->validate([
            'year' => 'required|integer|min:2000|max:2100|unique:frameworks,year',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'source_framework_id' => 'required_if:creation_method,inherit|exists:frameworks,id',
            'selected_sector_ids' => 'required_if:creation_method,inherit|array',
            'selected_sector_ids.*' => 'exists:sectors,id',
            'inherit_scope' => 'required_if:creation_method,inherit|in:full,sectors_only',
        ]);

        // If inheriting, validate source framework exists and is not draft
        if ($creationMethod === 'inherit') {
            $sourceFramework = Framework::findOrFail($validated['source_framework_id']);
            if ($sourceFramework->status === 'Draft') {
                return redirect()->back()
                    ->with('failure', 'You can only inherit from Active or Archived frameworks.')
                    ->withInput();
            }

            // Validate that all selected sectors belong to the source framework
            $selectedSectorIds = $request->input('selected_sector_ids', []);
            if (!empty($selectedSectorIds)) {
                $invalidSectors = Sector::whereIn('id', $selectedSectorIds)
                    ->where('framework_id', '!=', $sourceFramework->id)
                    ->count();

                if ($invalidSectors > 0) {
                    return redirect()->back()
                        ->with('failure', 'One or more selected sectors do not belong to the source framework.')
                        ->withInput();
                }
            }
        }

        // Check if there's already an active framework
        $hasActiveFramework = Framework::where('status', 'Active')->exists();

        DB::beginTransaction();
        try {
            // If creating a new active framework, archive the current active one
            if ($hasActiveFramework && $request->input('status') === 'Active') {
                Framework::where('status', 'Active')->update([
                    'status' => 'Archived',
                    'archived_by' => $user->id,
                    'archived_at' => now(),
                ]);
            }

            $framework = Framework::create([
                'year' => $validated['year'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'status' => $request->input('status', 'Draft'),
                'created_by' => $user->id,
            ]);

            // If inheriting, copy selected sectors (and optionally their structure) from source framework
            if ($creationMethod === 'inherit' && isset($sourceFramework)) {
                $selectedSectorIds = $request->input('selected_sector_ids', []);
                if (empty($selectedSectorIds)) {
                    DB::rollBack();

                    return redirect()->back()
                        ->with('failure', 'Please select at least one sector to inherit.')
                        ->withInput();
                }

                $inheritScope = $validated['inherit_scope'] ?? 'full';
                $this->copyFrameworkData($sourceFramework, $framework, $selectedSectorIds, $inheritScope);
            }

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Framework created successfully.',
                    'framework' => $framework,
                ]);
            }

            return redirect()->route('frameworks.index')->with('success', 'Framework created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create framework: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->route('frameworks.index')->with('failure', 'Failed to create framework: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Copy annual configuration from a source framework into a newly created target framework.
     * When $inheritScope is "sectors_only", only sector records are copied.
     * When $inheritScope is "full", selected sectors and their commitments, deliverables, and KPIs are copied.
     */
    protected function copyFrameworkData(
        Framework $sourceFramework,
        Framework $targetFramework,
        array $selectedSectorIds,
        string $inheritScope = 'full',
    ): void {
        $sourceSectors = Sector::where('framework_id', $sourceFramework->id)
            ->whereIn('id', $selectedSectorIds)
            ->get();

        foreach ($sourceSectors as $sourceSector) {
            $newSector = Sector::create([
                'sector_name' => $sourceSector->sector_name,
                'description' => $sourceSector->description,
                'framework_id' => $targetFramework->id,
            ]);

            if ($inheritScope === 'sectors_only') {
                continue;
            }

            $sourceCommitments = Commitment::where('framework_id', $sourceFramework->id)
                ->where('sector_id', $sourceSector->id)
                ->get();

            foreach ($sourceCommitments as $sourceCommitment) {
                $newCommitment = Commitment::create([
                    'name' => $sourceCommitment->name,
                    'type' => $sourceCommitment->type,
                    'description' => $sourceCommitment->description,
                    'status' => $sourceCommitment->status,
                    'budget' => $sourceCommitment->budget,
                    'sector_id' => $newSector->id,
                    'framework_id' => $targetFramework->id,
                ]);

                $sourceDeliverables = Deliverable::where('framework_id', $sourceFramework->id)
                    ->where('commitment_id', $sourceCommitment->id)
                    ->get();

                foreach ($sourceDeliverables as $sourceDeliverable) {
                    $newDeliverable = Deliverable::create([
                        'deliverable' => $sourceDeliverable->deliverable,
                        'status' => $sourceDeliverable->status,
                        'commitment_id' => $newCommitment->id,
                        'framework_id' => $targetFramework->id,
                    ]);

                    $sourceKpis = Kpi::where('framework_id', $sourceFramework->id)
                        ->where('deliverable_id', $sourceDeliverable->id)
                        ->get();

                    foreach ($sourceKpis as $sourceKpi) {
                        // Copy KPI structure only — not performance tracking / targets
                        Kpi::create([
                            'kpi' => $sourceKpi->kpi,
                            'unit_of_measurement' => $sourceKpi->unit_of_measurement,
                            'year' => $sourceKpi->year ?? null,
                            'deliverable_id' => $newDeliverable->id,
                            'framework_id' => $targetFramework->id,
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Display the specified framework
     */
    public function show(Framework $framework)
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        // Load framework with all necessary relationships
        $framework->load([
            'creator',
            'archiver',
            'sectors' => function($query) {
                $query->orderBy('sector_name');
            },
            'sectors.commitments',
            'sectors.commitments.deliverables',
            'sectors.commitments.deliverables.kpis'
        ]);

        // Get counts
        $sectorsCount = $framework->sectors()->count();
        $commitmentsCount = $framework->commitments()->count();
        $deliverablesCount = $framework->deliverables()->count();
        $kpisCount = $framework->kpis()->count();

        return view('pages.coordinator.framework-show', compact('framework', 'sectorsCount', 'commitmentsCount', 'deliverablesCount', 'kpisCount'));
    }

    /**
     * Archive a framework
     */
    public function archive(Request $request, Framework $framework)
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'You do not have permission to perform this action.');
        }

        if ($framework->isArchived()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Framework is already archived.',
                ], 400);
            }
            return redirect()->back()->with('failure', 'Framework is already archived.');
        }

        $framework->update([
            'status' => 'Archived',
            'archived_by' => $user->id,
            'archived_at' => now(),
        ]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Framework archived successfully.',
            ]);
        }

        return redirect()->route('frameworks.index')->with('success', 'Framework archived successfully.');
    }

    /**
     * Activate a framework (and archive current active one)
     */
    public function activate(Request $request, Framework $framework)
    {
        $user = Auth::user();

        if (!$user->isCoordinator()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to perform this action.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'You do not have permission to perform this action.');
        }

        if ($framework->isActive()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Framework is already active.',
                ], 400);
            }
            return redirect()->back()->with('failure', 'Framework is already active.');
        }

        DB::beginTransaction();
        try {
            // Archive current active framework if exists
            Framework::where('status', 'Active')->where('id', '!=', $framework->id)->update([
                'status' => 'Archived',
                'archived_by' => $user->id,
                'archived_at' => now(),
            ]);

            $framework->update([
                'status' => 'Active',
                'archived_by' => null,
                'archived_at' => null,
            ]);

            DB::commit();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Framework activated successfully.',
                ]);
            }

            return redirect()->route('frameworks.index')->with('success', 'Framework activated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to activate framework: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('failure', 'Failed to activate framework: ' . $e->getMessage());
        }
    }
}
