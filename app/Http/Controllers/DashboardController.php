<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware("auth");
    }


    public function index()
    {
        $user = Auth::user();
        
        // Allow Governor, Delivery Unit, System Admin, Sector Head, and Sector Admin to access dashboard
        // Only redirect other roles (if any) to their specific views
        if (!$user->isGovernor() && !$user->isDeliveryUnit() && !$user->isSystemAdmin() && !$user->isSectorHead() && !$user->isSectorAdmin()) {
            $userRole = UserRole::where(['user_id' => $user->id, 'role_status' => 'active'])->first();
            
            // Check if user role exists and has a valid entity_id
            if ($userRole && $userRole->entity_id) {
                $sector = Sector::find($userRole->entity_id);
                if ($sector) {
                    return redirect(route('sectors.view', [$userRole->entity_id]));
                }
            }
            
            // If no valid sector found, show error message but continue to show dashboard
            // This prevents redirect loop - we'll show the dashboard with an error message
            session()->flash('failure', 'Your account is not associated with a valid sector. Please contact the administrator.');
        }
        
        $year = date('Y');
        
        // Determine which sectors the user can access
        $accessibleSectorIds = [];
        $hasAccessToAllSectors = false;
        $userSector = null;
        
        if ($user->isGovernor() || $user->isSystemAdmin() || $user->canAccessAllSectors()) {
            // User has access to all sectors
            $hasAccessToAllSectors = true;
        } else {
            // User has access to specific sector(s)
            $userSector = $user->isSectorHead() ?: $user->isSectorAdmin();
            if ($userSector) {
                $accessibleSectorIds = [$userSector->id];
            } else {
                // Check for Facilitator or other roles with sector restrictions
                $assignedSectorIds = $user->getAssignedSectorIds();
                if (!empty($assignedSectorIds)) {
                    $accessibleSectorIds = $assignedSectorIds;
                }
            }
        }

        // Compute quarterly average performance per sector using performance_trackings
        // Performance is the average of (actual_value / milestone) * 100 for valid numeric entries with milestone > 0
        $rawQuarterly = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->join('performance_trackings as pt', 'pt.kpi_id', '=', 'k.id')
            ->where('pt.year', '=', $year)
            ->whereIn('pt.quarter', [1, 2, 3, 4])
            ->whereNotNull('d.end_date');
        
        // Filter by accessible sectors if user doesn't have access to all
        if (!$hasAccessToAllSectors && !empty($accessibleSectorIds)) {
            $rawQuarterly->whereIn('s.id', $accessibleSectorIds);
        }
        
        $rawQuarterly = $rawQuarterly
            ->select(
                's.id as sector_id',
                's.sector_name',
                'pt.quarter',
                DB::raw('AVG(CASE WHEN pt.milestone IS NOT NULL AND pt.actual_value IS NOT NULL AND pt.milestone > 0 AND pt.milestone REGEXP "^[0-9]+\\.?[0-9]*$" AND pt.actual_value REGEXP "^[0-9]+\\.?[0-9]*$" THEN (pt.actual_value / pt.milestone) * 100 ELSE NULL END) as avg_performance')
            )
            ->groupBy('s.id', 's.sector_name', 'pt.quarter')
            ->orderBy('s.sector_name')
            ->get();

        // Initialize sectors to ensure display even with no data
        if ($hasAccessToAllSectors) {
            $allSectors = DB::table('sectors')->select('id', 'sector_name')->orderBy('sector_name')->get();
        } else {
            $allSectors = DB::table('sectors')
                ->whereIn('id', $accessibleSectorIds)
                ->select('id', 'sector_name')
                ->orderBy('sector_name')
                ->get();
        }
        
        $sectorQuarterPerf = [];
        foreach ($allSectors as $sec) {
            $sectorQuarterPerf[$sec->id] = [
                'sector_name' => $sec->sector_name,
                1 => null,
                2 => null,
                3 => null,
                4 => null,
            ];
        }
        // Overlay computed values
        foreach ($rawQuarterly as $row) {
            if (isset($sectorQuarterPerf[$row->sector_id])) {
                $sectorQuarterPerf[$row->sector_id][$row->quarter] = $row->avg_performance !== null ? (float)$row->avg_performance : null;
            }
        }
        
        // Calculate statistics filtered by accessible sectors
        $commitmentsQuery = DB::table('commitments');
        $kpisQuery = DB::table('kpis as k')
            ->join('deliverables as d', 'k.deliverable_id', '=', 'd.id')
            ->join('commitments as c', 'd.commitment_id', '=', 'c.id');
        
        if (!$hasAccessToAllSectors && !empty($accessibleSectorIds)) {
            $commitmentsQuery->whereIn('sector_id', $accessibleSectorIds);
            $kpisQuery->whereIn('c.sector_id', $accessibleSectorIds);
        }
        
        $commitments = $commitmentsQuery->count();
        $kpis = $kpisQuery->count('k.id');
        
        // Calculate budget separately
        $budgetQuery = DB::table('commitments');
        if (!$hasAccessToAllSectors && !empty($accessibleSectorIds)) {
            $budgetQuery->whereIn('sector_id', $accessibleSectorIds);
        }
        $stateBudget = $budgetQuery->sum('budget') ?? 0;

        return view('pages.dashboard.index', [
            'year' => $year,
            'sectorQuarterPerf' => $sectorQuarterPerf,
            'commitments' => $commitments,
            'kpis' => $kpis,
            'stateBudget' => $stateBudget,
            'hasAccessToAllSectors' => $hasAccessToAllSectors,
            'userSector' => $userSector,
        ]);
    }

    public function statistics(Request $request)
    {
        $user = Auth::user();
        
        // Only allow Governor to access statistics
        if (!$user->isGovernor()) {
            return redirect()->route('dashboard')->with('failure', 'You do not have permission to access this page.');
        }

        // Get filter parameters
        $selectedSectorId = $request->input('sector_id');
        $year = $request->input('year', date('Y'));
        $quarter = $request->input('quarter', 'all');

        // Get all sectors from database
        $sectors = Sector::select('id', 'sector_name')
            ->orderBy('sector_name')
            ->get();

        // Calculate statistics based on filters
        $stats = $this->calculateStatistics($selectedSectorId, $year, $quarter, $request);

        return view('pages.dashboard.statistics', compact('sectors', 'stats', 'year', 'quarter', 'selectedSectorId'));
    }

    private function calculateStatistics($sectorId = null, $year = null, $quarter = null, $request = null)
    {
        $year = $year ?? date('Y');
        $quarter = $quarter ?? 'all';
        $isAllQuarters = ($quarter === 'all' || $quarter === null || $quarter === '');

        // Calculate average performance
        $avgPerformanceQuery = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->join('performance_trackings as pt', 'pt.kpi_id', '=', 'k.id')
            ->where('pt.year', '=', $year)
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('pt.milestone REGEXP "^[0-9]+\\.?[0-9]*$"')
            ->whereRaw('pt.actual_value REGEXP "^[0-9]+\\.?[0-9]*$"');
        
        if (!$isAllQuarters) {
            $avgPerformanceQuery->where('pt.quarter', '=', $quarter);
        } else {
            $avgPerformanceQuery->whereIn('pt.quarter', [1, 2, 3, 4]);
        }
        
        if ($sectorId) {
            $avgPerformanceQuery->where('s.id', '=', $sectorId);
        }
        
        $avgPerformance = $avgPerformanceQuery
            ->select(DB::raw('AVG((pt.actual_value / pt.milestone) * 100) as avg_performance'))
            ->value('avg_performance') ?? 0;

        // Get top performing sector
        $topSectorQuery = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->join('performance_trackings as pt', 'pt.kpi_id', '=', 'k.id')
            ->where('pt.year', '=', $year)
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('pt.milestone REGEXP "^[0-9]+\\.?[0-9]*$"')
            ->whereRaw('pt.actual_value REGEXP "^[0-9]+\\.?[0-9]*$"');
        
        if (!$isAllQuarters) {
            $topSectorQuery->where('pt.quarter', '=', $quarter);
        } else {
            $topSectorQuery->whereIn('pt.quarter', [1, 2, 3, 4]);
        }
        
        if ($sectorId) {
            $topSectorQuery->where('s.id', '=', $sectorId);
        }
        
        $topSector = $topSectorQuery
            ->select(
                's.id',
                's.sector_name',
                DB::raw('AVG((pt.actual_value / pt.milestone) * 100) as avg_performance'),
                DB::raw('COUNT(DISTINCT k.id) as kpi_count')
            )
            ->groupBy('s.id', 's.sector_name')
            ->orderBy('avg_performance', 'DESC')
            ->first();

        // Count pending verifications
        $pendingVerificationsQuery = DB::table('performance_trackings as pt')
            ->join('kpis as k', 'pt.kpi_id', '=', 'k.id')
            ->join('deliverables as d', 'k.deliverable_id', '=', 'd.id')
            ->join('commitments as c', 'd.commitment_id', '=', 'c.id')
            ->join('sectors as s', 'c.sector_id', '=', 's.id')
            ->where('pt.year', '=', $year)
            ->where('pt.confirmation_status', '!=', 'Confirmed');
        
        if (!$isAllQuarters) {
            $pendingVerificationsQuery->where('pt.quarter', '=', $quarter);
        } else {
            $pendingVerificationsQuery->whereIn('pt.quarter', [1, 2, 3, 4]);
        }
        
        if ($sectorId) {
            $pendingVerificationsQuery->where('s.id', '=', $sectorId);
        }
        
        $pendingVerifications = $pendingVerificationsQuery->distinct('s.id')->count('s.id');

        // Get sector comparison data
        $sectorComparisonQuery = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->join('performance_trackings as pt', 'pt.kpi_id', '=', 'k.id')
            ->where('pt.year', '=', $year)
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('pt.milestone REGEXP "^[0-9]+\\.?[0-9]*$"')
            ->whereRaw('pt.actual_value REGEXP "^[0-9]+\\.?[0-9]*$"');
        
        if (!$isAllQuarters) {
            $sectorComparisonQuery->where('pt.quarter', '=', $quarter);
        } else {
            $sectorComparisonQuery->whereIn('pt.quarter', [1, 2, 3, 4]);
        }
        
        if ($sectorId) {
            $sectorComparisonQuery->where('s.id', '=', $sectorId);
        }
        
        $sectorComparison = $sectorComparisonQuery
            ->select(
                's.id',
                's.sector_name',
                DB::raw('AVG((pt.actual_value / pt.milestone) * 100) as avg_performance'),
                DB::raw('COUNT(DISTINCT k.id) as kpi_count')
            )
            ->groupBy('s.id', 's.sector_name')
            ->orderBy('s.sector_name')
            ->get();

        // Get KPI status breakdown
        $kpiStatusBreakdown = $this->getKpiStatusBreakdown($sectorId, $year, $quarter);

        // Get detailed breakdown table data
        $detailedBreakdown = $this->getDetailedBreakdown($sectorId, $year, $quarter);

        return [
            'avg_performance' => round($avgPerformance, 1),
            'top_sector' => $topSector,
            'pending_verifications' => $pendingVerifications,
            'sector_comparison' => $sectorComparison,
            'kpi_status_breakdown' => $kpiStatusBreakdown,
            'detailed_breakdown' => $detailedBreakdown,
        ];
    }

    private function getKpiStatusBreakdown($sectorId = null, $year = null, $quarter = null)
    {
        $query = DB::table('kpis as k')
            ->join('deliverables as d', 'k.deliverable_id', '=', 'd.id')
            ->join('commitments as c', 'd.commitment_id', '=', 'c.id')
            ->join('sectors as s', 'c.sector_id', '=', 's.id')
            ->leftJoin('performance_trackings as pt', function($join) use ($year, $quarter) {
                $join->on('pt.kpi_id', '=', 'k.id')
                     ->where('pt.year', '=', $year)
                     ->where('pt.quarter', '=', $quarter);
            })
            ->when($sectorId, function($q) use ($sectorId) {
                return $q->where('s.id', '=', $sectorId);
            });

        $totalKpis = $query->clone()->distinct('k.id')->count('k.id');
        
        $onTrack = $query->clone()
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('(pt.actual_value / pt.milestone) * 100 >= 70')
            ->distinct('k.id')
            ->count('k.id');

        $atRisk = $query->clone()
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('(pt.actual_value / pt.milestone) * 100 >= 40')
            ->whereRaw('(pt.actual_value / pt.milestone) * 100 < 70')
            ->distinct('k.id')
            ->count('k.id');

        $delayed = $query->clone()
            ->whereNotNull('pt.actual_value')
            ->whereNotNull('pt.milestone')
            ->where('pt.milestone', '>', 0)
            ->whereRaw('(pt.actual_value / pt.milestone) * 100 < 40')
            ->distinct('k.id')
            ->count('k.id');

        return [
            'total' => $totalKpis,
            'on_track' => $onTrack,
            'at_risk' => $atRisk,
            'delayed' => $delayed,
            'on_track_pct' => $totalKpis > 0 ? round(($onTrack / $totalKpis) * 100) : 0,
            'at_risk_pct' => $totalKpis > 0 ? round(($atRisk / $totalKpis) * 100) : 0,
            'delayed_pct' => $totalKpis > 0 ? round(($delayed / $totalKpis) * 100) : 0,
        ];
    }

    private function getDetailedBreakdown($sectorId = null, $year = null, $quarter = null, $request = null)
    {
        $isAllQuarters = ($quarter === 'all' || $quarter === null || $quarter === '');
        
        $query = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->leftJoin('kpi_targets as kt', function($join) use ($year) {
                $join->on('kt.kpi_id', '=', 'k.id')
                     ->where('kt.year', '=', $year);
            })
            ->leftJoin('performance_trackings as pt', function($join) use ($year, $quarter, $isAllQuarters) {
                $join->on('pt.kpi_id', '=', 'k.id')
                     ->where('pt.year', '=', $year);
                if (!$isAllQuarters) {
                    $join->where('pt.quarter', '=', $quarter);
                } else {
                    $join->whereIn('pt.quarter', [1, 2, 3, 4]);
                }
            })
            ->whereNotNull('kt.target')
            ->when($sectorId, function($q) use ($sectorId) {
                return $q->where('s.id', '=', $sectorId);
            })
            ->select(
                's.id as sector_id',
                's.sector_name',
                'c.name as commitment_name',
                'k.id as kpi_id',
                'k.kpi',
                'k.target_value as baseline',
                'kt.target as target_value',
                'pt.actual_value',
                'k.unit_of_measurement',
                DB::raw('CASE 
                    WHEN pt.actual_value IS NULL OR pt.milestone IS NULL OR pt.milestone = 0 THEN "Pending"
                    WHEN (pt.actual_value / pt.milestone) * 100 >= 100 THEN "Exceptional"
                    WHEN (pt.actual_value / pt.milestone) * 100 >= 70 THEN "Target Met"
                    WHEN (pt.actual_value / pt.milestone) * 100 >= 40 THEN "At Risk"
                    ELSE "Delayed"
                END as status'),
                DB::raw('CASE 
                    WHEN pt.actual_value IS NOT NULL AND pt.milestone IS NOT NULL AND pt.milestone > 0 
                    THEN ROUND(((pt.actual_value - pt.milestone) / pt.milestone) * 100, 1)
                    ELSE NULL
                END as variance')
            )
            ->orderBy('s.sector_name')
            ->orderBy('c.name');

        // Paginate the results (15 items per page)
        $perPage = 15;
        $currentPage = $request ? $request->input('page', 1) : 1;
        
        // Get total count
        $total = $query->count();
        
        // Get paginated results
        $items = $query->skip(($currentPage - 1) * $perPage)
                      ->take($perPage)
                      ->get();
        
        // Create paginator instance with preserved query parameters
        $path = $request ? $request->url() : url()->current();
        $queryParams = $request ? $request->except('page') : [];
        
        $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $path,
                'query' => $queryParams,
            ]
        );
        
        // Set the page name to 'page' (default)
        $paginator->setPageName('page');
        
        return $paginator;
    }
}
