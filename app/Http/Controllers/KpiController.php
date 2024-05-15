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
//        return $request;
        if (is_null($request->id))
            $tracking = new PerformanceTracking();
        else
            $tracking = PerformanceTracking::find($request->id);
        $tracking->fill($request->all());
        $tracking->save();

        $user = Auth::user();
        $receiverId = UserRole::where(['role' => 'Delivery Department', 'role_status' => 'Active'])->pluck('user_id');
        $receiver = User::whereIn('id', $receiverId)->first();

        $body = $user->role()->role . ' of ' . $user->sector()->sector_name . ' made a submission on ' . $tracking->kpi->kpi . '. It awaits your review';
        $forme = 'Your request on ' . $tracking->kpi->kpi . ' has been submitted to Delivery Department. It is waiting for review';

        Notification::make($user, $receiver, $tracking, 'Review Request', $body, 'Tracking Submitted');
        Notification::make($receiver, $user, $tracking, 'Tracking Submitted', $forme, 'System');

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
        foreach ($request->target as $key => $value) {
            $target = KpiTarget::find($key);
            $target->target = $value;
            $target->save();
        }
        return back();
    }

}
