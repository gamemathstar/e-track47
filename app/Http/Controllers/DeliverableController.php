<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\DeliveryKpi;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\PerformanceTracking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeliverableController extends Controller
{
    //

    public function __construct()
    {
        $this->middleware("auth");
    }

    public function store(Request $request)
    {
//        return $request;
        $request->validate([
            'commitment_id' => "required",
            'deliverable' => "required",
            'budget' => "required",
            'start_date' => "required",
            'end_date' => "required",
            'status' => "required",
        ]);

        Deliverable::create($request->all());

        return redirect()->back()->with('success', 'Deliverable created successfully');
    }

    public function storeTracking(Request $request)
    {
//        return $request;
        $request->validate([
            'delivery_department_value' => "required",
            'delivery_department_remark' => "required",
            'confirmation_status' => "required",
//            'id'=>'required:exists'
        ]);
        $pt = PerformanceTracking::find($request->id);
        if($pt){
            $pt->delivery_department_value = $request->delivery_department_value;
            $pt->delivery_department_remark = $request->delivery_department_remark;
            $pt->confirmation_status = $request->confirmation_status;
            $pt->save();
        }

        return redirect()->back()->with('success', 'Deliverable created successfully');
    }

    public function view(Request $request)
    {
        $deliverable = Deliverable::find($request->id);
        $commitment = Commitment::find($deliverable->commitment_id);
        return view('pages.sector.deliverable', compact('deliverable', 'commitment'));
    }

    public function kpis(Request $request,Deliverable $deliverable)
    {
        $kpis = $deliverable->kpis()->get();
        $year = $request->year?:2024;

        foreach ($kpis as $kpi){
            $targt = KpiTarget::where(['year'=>$year,'kpi_id'=>$kpi->id])->first();
            if(!$targt){
                $targt = new KpiTarget();
                $targt->year =$year;
                $targt->kpi_id =$kpi->id;
                $targt->target ="";
                $targt->save();
            }
        }
        $targets = Kpi::leftJoin("kpi_targets",function ($join)use ($year){
            $join->on("kpi_targets.kpi_id","=","kpis.id")
                ->on('year',"=",DB::raw($year));
        })
            ->where(['deliverable_id'=>$deliverable->id])->get();
        return view('pages.sector.kpis', compact('deliverable', 'kpis','year','targets'));
    }

    public function addKPI(Request $request)
    {
//        return $request;
        $deliverable = Deliverable::find($request->deliverable_id);
        if ($deliverable) {
            $kpi = new DeliveryKpi();
            $kpi->deliverable_id = $request->deliverable_id;
            $kpi->year = $request->year;
            $kpi->kpi_id = $request->kpi_id;
            $kpi->target = $request->target;
            $kpi->actual_value = $request->actual_value;
            if ($kpi->save()) {
                return ['status' => 1, 'message' => 'KPI added'];
            } else {
                return ['status' => 0, 'message' => 'Failed to add KPI'];
            }
        }

        return ['status' => 0, 'message' => 'Invalid Deliverable'];
    }

    public function delete(Deliverable $deliverable)
    {
        if (count($deliverable->kpis()->get()) == 0) {
            $deliverable->delete();
            return back()->with('success', 'Deliverable deleted successfully');
        } else
            return back()->with('failure',
                'Oops! This deliverable cannot be deleted as it has KPI(s) attached. Remove the KPI(s) and try again');
    }
}
