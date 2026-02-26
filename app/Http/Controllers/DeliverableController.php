<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\DeliveryKpi;
use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Traits\ChecksDataEntryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliverableController extends Controller
{
    use ChecksDataEntryAccess;

    //

    public function __construct()
    {
        $this->middleware("auth");
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Only PDCU users can create deliverables
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can create deliverables.');
        }

        // Check data entry access
        $request->validate([
            'commitment_id' => "required",
        ]);

        $sectorId = $this->getSectorIdFromCommitment($request->commitment_id);
        if ($sectorId) {
            $accessCheck = $this->checkDataEntryAccess($sectorId);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

//        return $request;
        $request->validate([
            'commitment_id' => "required",
            'deliverable' => "required",
//            'budget' => "required",
            'start_date' => "required",
            'end_date' => "required",
            'status' => "required",
        ]);

        Deliverable::create($request->all());

        return redirect()->back()->with('success', 'Deliverable created successfully');
    }

    public function storeTracking(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return redirect()->back()->with('failure', 'User not authenticated');
        }

        $isPDCU = $user->isDeliveryUnit();

        // Define validation rules - milestone is not editable in verify modal
        $validationRules = [
            'id' => 'required|exists:performance_trackings,id',
            'delivery_department_remark' => "required",
            'confirmation_status' => "required",
        ];

        $request->validate($validationRules);

        $pt = PerformanceTracking::find($request->id);
        if (!$pt) {
            return redirect()->back()->with('failure', 'Performance tracking record not found');
        }

        // For PDCU users, only allow verifying records that are approved by Sector Head
        if ($isPDCU && !$pt->isVisibleToPDCU()) {
            return redirect()->back()->with('failure', 'This performance tracking record is not yet approved by Sector Head and cannot be verified.');
        }

        $userRole = $user->role();
        if (!$userRole) {
            return redirect()->back()->with('failure', 'User role not found');
        }

        // Note: delivery_department_value field removed from form as per requirements
        // Milestone is not editable in verify modal - it's set by PDCU during creation
        $pt->delivery_department_remark = $request->delivery_department_remark;
        $pt->confirmation_status = $request->confirmation_status;
        $pt->save();

        try {
            // Check if user has any delivery unit role
            if ($user->isDeliveryUnit()) {
                Notification::submitTrackingReview($pt);
            } else {
                Notification::submitTrackingForRewiew($pt);
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            Log::error('Notification error in storeTracking: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Delivery ' . $request->confirmation_status);
    }

    public function view(Request $request)
    {
        $deliverable = Deliverable::find($request->id);
        $commitment = Commitment::find($deliverable->commitment_id);
        return view('pages.sector.deliverable', compact('deliverable', 'commitment'));
    }

    public function kpis(Request $request, Deliverable $deliverable)
    {
        $user = Auth::user();
        $kpis = $deliverable->kpis()->get();
        $year = $request->year ?: 2024;

        // Note: PDCU filtering is now handled in the view using getYearTracks() and getQuarterTrack()
        // with $onlyApproved parameter. This ensures consistent filtering across all queries.

        foreach ($kpis as $kpi) {
            $targt = KpiTarget::where(['year' => $year, 'kpi_id' => $kpi->id])->first();
            if (!$targt) {
                $targt = new KpiTarget();
                $targt->year = $year;
                $targt->kpi_id = $kpi->id;
                $targt->target = "";
                $targt->save();
            }
        }
        $targets = Kpi::leftJoin("kpi_targets", function ($join) use ($year) {
            $join->on("kpi_targets.kpi_id", "=", "kpis.id")
                ->on('kpi_targets.year', "=", DB::raw($year));
        })
            ->where(['kpis.deliverable_id' => $deliverable->id])->get();
        return view('pages.sector.kpis', compact('deliverable', 'kpis', 'year', 'targets', 'user'));
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

    public function update(Request $request)
    {
        $user = Auth::user();

        // Only PDCU users can update deliverables
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can update deliverables.');
        }

        $request->validate([
            'deliverable_id' => 'required|exists:deliverables,id',
        ]);

        $deliverable = Deliverable::find($request->deliverable_id);

        // Check if data is locked (confirmed by Coordinator)
        if ($deliverable) {
            $hasConfirmedTracking = PerformanceTracking::whereHas('kpi', function ($q) use ($deliverable) {
                $q->where('deliverable_id', $deliverable->id);
            })
                ->where('confirmation_status', 'Confirmed')
                ->whereNotNull('coordinator_confirmed_at')
                ->exists();

            if ($hasConfirmedTracking) {
                return redirect()->back()->with('failure', 'This deliverable has confirmed performance tracking and cannot be modified.');
            }
        }

        // Check data entry access
        if ($deliverable) {
            $sectorId = $this->getSectorIdFromDeliverable($request->deliverable_id);
            if ($sectorId) {
                $accessCheck = $this->checkDataEntryAccess($sectorId);
                if ($accessCheck) {
                    return $accessCheck;
                }
            }
        }

        $request->validate([
            'deliverable_id' => 'required|exists:deliverables,id',
            'deliverable' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'status' => 'required|string|in:Not Started,In Progress,Completed'
        ]);

        if (!$deliverable) {
            return redirect()->back()->with('failure', 'Deliverable not found');
        }

        // Update deliverable fields
        $deliverable->deliverable = $request->deliverable;
        $deliverable->start_date = $request->start_date;
        $deliverable->end_date = $request->end_date;
        $deliverable->status = $request->status;

        $deliverable->save();

        return redirect()->back()->with('success', 'Deliverable updated successfully');
    }

    public function delete(Deliverable $deliverable)
    {
        $user = Auth::user();

        // Only PDCU users can delete deliverables
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can delete deliverables.');
        }

        // Check if data is locked (confirmed by Coordinator)
        $hasConfirmedTracking = PerformanceTracking::whereHas('kpi', function ($q) use ($deliverable) {
            $q->where('deliverable_id', $deliverable->id);
        })
            ->where('confirmation_status', 'Confirmed')
            ->whereNotNull('coordinator_confirmed_at')
            ->exists();

        if ($hasConfirmedTracking) {
            return redirect()->back()->with('failure', 'This deliverable has confirmed performance tracking and cannot be deleted.');
        }

        // Check data entry access
        $sectorId = $this->getSectorIdFromDeliverable($deliverable->id);
        if ($sectorId) {
            $accessCheck = $this->checkDataEntryAccess($sectorId);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

        if (count($deliverable->kpis()->get()) == 0) {
            $deliverable->delete();
            return back()->with('success', 'Deliverable deleted successfully');
        } else
            return back()->with('failure',
                'Oops! This deliverable cannot be deleted as it has KPI(s) attached. Remove the KPI(s) and try again');
    }
}
