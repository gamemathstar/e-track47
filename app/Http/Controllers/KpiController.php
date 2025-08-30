<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        if ($request->hasFile('files')) {
            $request->validate([
                'files.*' => 'required|mimes:jpg,jpeg,png,xlsx,xls,doc,docx,pdf|max:20480',
            ]);
        }
        if (is_null($request->id))
            $tracking = new PerformanceTracking();
        else
            $tracking = PerformanceTracking::find($request->id);
        $tracking->fill($request->all());
        $tracking->save();

        if ($request->file('files')) {
            $target = Auth::user()->role()->target_entity;
            foreach ($request->file('files') as $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $fileName = Str::random(10) . '.' . $extension;
                $path = $file->storeAs('uploads', $fileName, 'public');

                $tracking->files()->create([
                    'name' => $originalName,
                    'path' => $path,
                    'type' => $extension,
                    'size' => $file->getSize(),
                    'attached_by' => $target
                ]);
            }
        }

        Notification::submitTrackingForRewiew($tracking);

        return back();
    }

    public function tracking(Kpi $kpi, $track_id)
    {
        $track = $kpi->performance_trackings()->where(['id' => $track_id])->first();
        return view('pages.sector.performance', compact('kpi', 'track'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
            'kpi' => 'required|string|max:255',
            'target_value' => 'required|numeric',
            'unit_of_measurement' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date'
        ]);

        $kpi = Kpi::find($request->kpi_id);
        
        if (!$kpi) {
            return redirect()->back()->with('failure', 'KPI not found');
        }

        // Update KPI fields
        $kpi->kpi = $request->kpi;
        $kpi->target_value = $request->target_value;
        $kpi->unit_of_measurement = $request->unit_of_measurement;
        $kpi->start_date = $request->start_date;
        $kpi->end_date = $request->end_date;

        $kpi->save();

        return redirect()->back()->with('success', 'KPI updated successfully');
    }

    public function delete(Kpi $kpi)
    {
        $kpi->delete();
        return back()->with('success', 'KPI deleted successfully');
    }

    public function saveTarget(Request $request)
    {
        foreach ($request->target as $key => $value) {
            $target = KpiTarget::find($key);
            $target->target = $value;
            $target->save();
        }
        return back();
    }

}
