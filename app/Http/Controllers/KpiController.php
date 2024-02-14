<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware("auth");
    }

    public function store(Request $request)
    {
        $request->validate([
            'kpi' => 'required',
            'end_date' => 'required',
            'start_date' => 'required',
            'target_value' => 'required',
            'deliverable_id' => 'required',
            'unit_of_measurement' => 'required',
        ]);

        Kpi::create($request->all());

        return back();
    }

    public function storeTracking(Request $request)
    {
        if (is_null($request->id))
            $tracking = new PerformanceTracking();
        else
            $tracking = PerformanceTracking::find($request->id);
        $tracking->fill($request->all());
        $tracking->save();

        return back();
    }

    public function tracking(Kpi $kpi, $track_id)
    {
        $track = $kpi->performanceTracking()->where(['id' => $track_id])->first();
        return view('pages.sector.performance', compact('kpi', 'track'));
    }

    public function delete(Kpi $kpi)
    {
        $kpi->delete();
        return back()->with('success', 'KPI deleted successfully');
    }

    public function saveTarget(Request $request)
    {
        foreach($request->target as $key=>$value){
            $target = KpiTarget::find($key);
            $target->target = $value;
            $target->save();
        }
        return back();
    }

}
