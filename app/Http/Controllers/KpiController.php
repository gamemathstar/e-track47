<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
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

    public function tracking(Kpi $kpi)
    {
        $tracking = $kpi->performanceTracking()->get();
        return view('pages.sector.performance', compact('kpi', 'tracking'));
    }

}
