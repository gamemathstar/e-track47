<?php

namespace App\Http\Controllers;

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
        if (!$user->isGovernor() && !$user->isDeliveryDepartment() && !$user->isSystemAdmin()) {
            $userRole = UserRole::where(['user_id' => $user->id])->first();
            return redirect(route('sectors.view', [$userRole->entity_id]));
        }
        $year = date('Y');

        // Compute quarterly average performance per sector using performance_trackings
        // Performance is the average of (actual_value / milestone) * 100 for valid numeric entries with milestone > 0
        $rawQuarterly = DB::table('sectors as s')
            ->join('commitments as c', 'c.sector_id', '=', 's.id')
            ->join('deliverables as d', 'd.commitment_id', '=', 'c.id')
            ->join('kpis as k', 'k.deliverable_id', '=', 'd.id')
            ->join('performance_trackings as pt', 'pt.kpi_id', '=', 'k.id')
            ->where('pt.year', '=', $year)
            ->whereIn('pt.quarter', [1, 2, 3, 4])
            ->whereNotNull('d.end_date')
            ->select(
                's.id as sector_id',
                's.sector_name',
                'pt.quarter',
                DB::raw('AVG(CASE WHEN pt.milestone IS NOT NULL AND pt.actual_value IS NOT NULL AND pt.milestone > 0 AND pt.milestone REGEXP "^[0-9]+\\.?[0-9]*$" AND pt.actual_value REGEXP "^[0-9]+\\.?[0-9]*$" THEN (pt.actual_value / pt.milestone) * 100 ELSE NULL END) as avg_performance')
            )
            ->groupBy('s.id', 's.sector_name', 'pt.quarter')
            ->orderBy('s.sector_name')
            ->get();

        // Initialize all sectors to ensure display even with no data
        $allSectors = DB::table('sectors')->select('id', 'sector_name')->orderBy('sector_name')->get();
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

        return view('pages.dashboard.index', [
            'year' => $year,
            'sectorQuarterPerf' => $sectorQuarterPerf,
        ]);
    }
}
