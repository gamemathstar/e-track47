<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChartController extends Controller
{
    //
    public function kpiPerformance(Request $request)
    {
        $user = Auth::user();
        return response()->json($user->sectorPerformanceKpi($request->year));
    }

    public function kpiPerformanceRatio(Request $request)
    {
        $user = Auth::user();
        return response()->json($user->kpiPerformanceRatio());
    }
}
