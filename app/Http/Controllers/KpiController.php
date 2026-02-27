<?php

namespace App\Http\Controllers;

use App\Models\Kpi;
use App\Models\KpiTarget;
use App\Models\Notification;
use App\Models\PerformanceTracking;
use App\Models\User;
use App\Models\UserRole;
use App\Traits\ChecksDataEntryAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KpiController extends Controller
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

        // Only PDCU users can create KPIs
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can create KPIs.');
        }

        // Check data entry access
        $request->validate([
            'deliverable_id' => 'required',
        ]);

        $sectorId = $this->getSectorIdFromDeliverable($request->deliverable_id);
        if ($sectorId) {
            $accessCheck = $this->checkDataEntryAccess($sectorId, $request->year);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

        $validated = $request->validate([
            'kpi' => 'required',
            'year' => 'required|integer|min:2000|max:2100',
            'target_value' => 'required',
            'deliverable_id' => 'required',
            'unit_of_measurement' => 'required',
        ]);

        Kpi::create($validated);

        return back();
    }

    public function storeTracking(Request $request)
    {
        $user = Auth::user();
        $isPDCU = $user->isDeliveryUnit();
        $isDataAdmin = $user->isDataAdmin();

        // Allow PDCU to create milestone-only records, and Data Admin to update records
        if (!$isPDCU && !$isDataAdmin) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to submit performance tracking.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'You do not have permission to submit performance tracking.');
        }

        // Check if this is an update or new entry
        // track_id can be empty string, so check for both null and empty
        $requestTrackId = $request->id ?? $request->input('track_id');
        $isUpdate = !empty($requestTrackId);
        $trackId = $isUpdate ? $requestTrackId : null;

        // Check existing record if this is an update
        $existingTracking = null;
        if ($isUpdate) {
            $existingTracking = PerformanceTracking::find($trackId);
            if (!$existingTracking) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Performance tracking record not found.',
                    ], 404);
                }
                return redirect()->back()->with('failure', 'Performance tracking record not found.');
            }

            // Check if data is locked (confirmed by Coordinator)
            if ($existingTracking->isLockedFromSectorModification()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This data has been confirmed by PDCU and cannot be modified.',
                    ], 403);
                }
                return redirect()->back()->with('failure', 'This data has been confirmed by PDCU and cannot be modified.');
            }

            // Determine if this is a milestone-only update (PDCU can update) or actual value update (Data Admin only)
            $isMilestoneOnlyUpdate = $existingTracking->actual_value === null || $existingTracking->actual_value == 0;

            // For updates with actual values, only Data Admin can update
            if (!$isMilestoneOnlyUpdate && !$isDataAdmin) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only Data Admin users can update performance tracking records with actual values.',
                    ], 403);
                }
                return redirect()->back()->with('failure', 'Only Data Admin users can update performance tracking records with actual values.');
            }

            // Data Admin editing rules: Cannot edit if approved by Sector Head (unless rejected by Facilitator)
            if ($isDataAdmin && $existingTracking->sector_head_approved_by) {
                // Allow editing only if rejected by Facilitator
                if ($existingTracking->facilitator_decision !== 'Reject') {
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'This record has been approved by Sector Head and cannot be modified. It can only be edited if rejected by Facilitator.',
                        ], 403);
                    }
                    return redirect()->back()->with('failure', 'This record has been approved by Sector Head and cannot be modified. It can only be edited if rejected by Facilitator.');
                }
            }

            // For milestone-only updates, only PDCU can update
            if ($isMilestoneOnlyUpdate && !$isPDCU) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Only PDCU users can update milestone values.',
                    ], 403);
                }
                return redirect()->back()->with('failure', 'Only PDCU users can update milestone values.');
            }
        }

        // For new entries, only PDCU can create milestone-only records
        if (!$isUpdate && !$isPDCU) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only PDCU users can create performance tracking records.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'Only PDCU users can create performance tracking records.');
        }

        // Validate file uploads if present
        if ($request->hasFile('files')) {
            $request->validate([
                'files.*' => 'required|mimes:jpg,jpeg,png,xlsx,xls,doc,docx,pdf|max:20480',
            ]);
        }

        // Define validation rules based on user role and action
        $validationRules = [
            'kpi_id' => 'required|exists:kpis,id',
            'quarter' => 'required|integer|in:1,2,3,4',
            'year' => 'required|integer|min:2000|max:2100',
        ];

        if ($isUpdate) {
            // This is an update - determine if it's PDCU updating milestone or Data Admin updating actual values
            if ($isPDCU) {
                // PDCU updating milestone only
                $validationRules['milestone'] = 'required|numeric|min:0';
                // Other fields are optional for PDCU when updating milestone
                $validationRules['tracking_date'] = 'nullable|date';
                $validationRules['actual_value'] = 'nullable|numeric|min:0';
                $validationRules['remarks'] = 'nullable|string|max:1000';
            } else {
                // Data Admin updating existing record (actual values)
                $validationRules['tracking_date'] = 'required|date';
                $validationRules['actual_value'] = 'required|numeric|min:0';
                $validationRules['remarks'] = 'nullable|string|max:1000';
                // Milestone is not in validation - it's readonly and preserved from existing record
            }
        } else {
            // For new entries (PDCU creating new record with milestone only)
            // Only milestone is required, other fields are optional/nullable
            $validationRules['milestone'] = 'required|numeric|min:0';
            $validationRules['tracking_date'] = 'nullable|date';
            $validationRules['actual_value'] = 'nullable|numeric|min:0';
            $validationRules['remarks'] = 'nullable|string|max:1000';
        }

        // Validate required fields
        $validated = $request->validate($validationRules);

        if ($isUpdate) {
            // Updating an existing record - use the one we already found
            $tracking = $existingTracking;

            if ($isPDCU) {
                // PDCU is updating milestone only
                $tracking->milestone = $validated['milestone'];
                // Preserve other fields if they exist, but allow updates if provided
                if (isset($validated['tracking_date'])) {
                    $tracking->tracking_date = $validated['tracking_date'];
                }
                if (isset($validated['actual_value'])) {
                    $tracking->actual_value = $validated['actual_value'];
                }
                if (isset($validated['remarks'])) {
                    $tracking->remarks = $validated['remarks'];
                }
                // Keep status as is (should be 'Not Confirmed' if no actual_value yet)
                if (!$tracking->actual_value) {
                    $tracking->confirmation_status = 'Not Confirmed';
                }
            } else {
                // Data Admin is updating actual values
                // Preserve milestone - Data Admin cannot change it
                $tracking->tracking_date = $validated['tracking_date'];
                $tracking->actual_value = $validated['actual_value'];
                $tracking->remarks = $validated['remarks'] ?? null;

                // If this was a rejected record, clear facilitator decision fields when Data Admin resubmits
                if ($tracking->facilitator_decision === 'Reject') {
                    $tracking->facilitator_decision = null;
                    $tracking->facilitator_rejection_reason = null;
                    $tracking->facilitator_confirmed_at = null;
                    $tracking->facilitator_confirmed_by = null;
                }

                // Set status to pending Sector Head approval
                if (!$tracking->sector_head_approved_at) {
                    $tracking->confirmation_status = 'Pending Sector Head Approval';
                }
            }
        } else {
            // PDCU is creating a new record - but first check if one already exists
            // Check for existing record (same KPI, quarter, and year)
            $existing = PerformanceTracking::where('kpi_id', $validated['kpi_id'])
                ->where('quarter', $validated['quarter'])
                ->where('year', $validated['year'])
                ->first();

            if ($existing) {
                // Record exists - check if it can be updated (no actual_value set by Data Admin)
                if ($existing->actual_value !== null && $existing->actual_value != 0) {
                    // Data Admin has already set actual_value, cannot update
                    if ($request->ajax() || $request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Performance tracking for this KPI, quarter, and year already exists with actual values. Please contact Data Admin.',
                        ], 422);
                    }
                    return redirect()->back()->with('failure', 'Performance tracking for this KPI, quarter, and year already exists with actual values. Please contact Data Admin.');
                }

                // Record exists but no actual_value - update it instead of creating new
                $tracking = $existing;
                $tracking->milestone = $validated['milestone'];
                // Preserve other fields, but allow updates if provided
                if (isset($validated['tracking_date'])) {
                    $tracking->tracking_date = $validated['tracking_date'];
                }
                if (isset($validated['remarks'])) {
                    $tracking->remarks = $validated['remarks'];
                }
                // Keep status as 'Not Confirmed' if no actual_value yet
                if (!$tracking->actual_value) {
                    $tracking->confirmation_status = 'Not Confirmed';
                }
            } else {
                // No existing record - create new one
                $tracking = new PerformanceTracking();
                $tracking->kpi_id = $validated['kpi_id'];
                $tracking->quarter = $validated['quarter'];
                $tracking->year = $validated['year'];
                $tracking->milestone = $validated['milestone'];
                $tracking->tracking_date = $validated['tracking_date'] ?? null;
                $tracking->actual_value = $validated['actual_value'] ?? null;
                $tracking->remarks = $validated['remarks'] ?? null;
                // Set initial status - PDCU created records are ready for Data Admin to fill
                $tracking->confirmation_status = 'Not Confirmed';
            }
        }

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

        // Notify Sector Head when Data Admin submits
        if (is_null($request->id) || !$tracking->sector_head_approved_at) {
            Notification::notifySectorHeadForApproval($tracking);
        }

        // Handle AJAX requests
        if ($request->ajax() || $request->wantsJson()) {
            $message = $isUpdate
                ? 'Milestone updated successfully.'
                : 'Milestone created successfully.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'redirect' => url()->previous()
            ]);
        }

        return back()->with('success', 'Performance tracking saved successfully.');
    }

    public function tracking(Kpi $kpi, $track_id)
    {
        $user = Auth::user();
        $track = $kpi->performanceTracking()->where('id', $track_id)->first();

        if (!$track) {
            return redirect()->back()->with('failure', 'Performance tracking record not found.');
        }

        // For PDCU users, only allow viewing if the record is approved by Sector Head
        if ($user->isDeliveryUnit() && !$track->isVisibleToPDCU()) {
            return redirect()->back()->with('failure', 'This performance tracking record is not yet approved by Sector Head and cannot be viewed.');
        }

        return view('pages.sector.performance', compact('kpi', 'track'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        // Only PDCU users can update KPIs
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can update KPIs.');
        }

        $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
        ]);

        $kpi = Kpi::find($request->kpi_id);

        // Check if data is locked (confirmed by Coordinator)
        if ($kpi) {
            $hasConfirmedTracking = PerformanceTracking::where('kpi_id', $kpi->id)
                ->where('confirmation_status', 'Confirmed')
                ->whereNotNull('coordinator_confirmed_at')
                ->exists();

            if ($hasConfirmedTracking) {
                return redirect()->back()->with('failure', 'This KPI has confirmed performance tracking and cannot be modified.');
            }
        }

        // Check data entry access
        if ($kpi) {
            $sectorId = $this->getSectorIdFromKpi($request->kpi_id);
            if ($sectorId) {
                $accessCheck = $this->checkDataEntryAccess($sectorId, $request->year);
                if ($accessCheck) {
                    return $accessCheck;
                }
            }
        }

        $request->validate([
            'kpi_id' => 'required|exists:kpis,id',
            'kpi' => 'required|string|max:255',
            'target_value' => 'required|numeric',
            'unit_of_measurement' => 'required|string|max:255',
            'year' => 'required|integer|min:2000|max:2100'
        ]);

        if (!$kpi) {
            return redirect()->back()->with('failure', 'KPI not found');
        }

        // Update KPI fields
        $kpi->kpi = $request->kpi;
        $kpi->target_value = $request->target_value;
        $kpi->unit_of_measurement = $request->unit_of_measurement;
        $kpi->year = $request->year;

        $kpi->save();

        return redirect()->back()->with('success', 'KPI updated successfully');
    }

    public function delete(Kpi $kpi)
    {
        $user = Auth::user();

        // Only PDCU users can delete KPIs
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'Only PDCU staff can delete KPIs.');
        }

        // Check if data is locked (confirmed by Coordinator)
        $hasConfirmedTracking = PerformanceTracking::where('kpi_id', $kpi->id)
            ->where('confirmation_status', 'Confirmed')
            ->whereNotNull('coordinator_confirmed_at')
            ->exists();

        if ($hasConfirmedTracking) {
            return redirect()->back()->with('failure', 'This KPI has confirmed performance tracking and cannot be deleted.');
        }

        // Check data entry access
        $sectorId = $this->getSectorIdFromKpi($kpi->id);
        if ($sectorId) {
            $accessCheck = $this->checkDataEntryAccess($sectorId);
            if ($accessCheck) {
                return $accessCheck;
            }
        }

        $kpi->delete();
        return back()->with('success', 'KPI deleted successfully');
    }

    public function saveTarget(Request $request)
    {
        $user = Auth::user();

        // Only PDCU users (Coordinator, Deputy Coordinator, Facilitator) can set targets
        if (!$user->isDeliveryUnit()) {
            return redirect()->back()->with('failure', 'You do not have permission to set KPI targets. Only PDCU users can set targets.');
        }

        foreach ($request->target as $key => $value) {
            $target = KpiTarget::find($key);
            if ($target) {
                $target->target = $value;
                $target->save();
            }
        }
        return back()->with('success', 'KPI targets updated successfully.');
    }

    public function approveData(Request $request)
    {
        $user = Auth::user();

        // Only Sector Head can approve data
        if (!$user->isSectorHead()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Sector Head users can approve performance tracking data.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'Only Sector Head users can approve performance tracking data.');
        }

        $sector = $user->isSectorHead();
        if (!$sector) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sector not found for this user.',
                ], 404);
            }
            return redirect()->back()->with('failure', 'Sector not found for this user.');
        }

        // Validate year and optional quarter
        $request->validate([
            'year' => 'required|integer|min:2000|max:2100',
            'quarter' => 'nullable|integer|in:1,2,3,4',
        ]);

        $year = $request->input('year');
        $quarter = $request->input('quarter');

        // Get all pending performance tracking records for this sector, year, and quarter
        // Only include records where Data Admin has supplied actual_value
        // Must have status 'Pending Sector Head Approval' to ensure it's a Data Admin submission
        $query = PerformanceTracking::whereHas('kpi', function ($kpiQuery) use ($sector) {
            $kpiQuery->whereHas('deliverable', function ($deliverableQuery) use ($sector) {
                $deliverableQuery->whereHas('commitment', function ($commitmentQuery) use ($sector) {
                    $commitmentQuery->where('sector_id', $sector->id);
                });
            });
        })
            ->whereNull('sector_head_approved_by')
            ->whereNotNull('actual_value')
            ->where('actual_value', '!=', 0)
            ->where('year', $year);

        if ($quarter) {
            $query->where('quarter', $quarter);
        }

        $pendingTrackings = $query->get();

        if ($pendingTrackings->isEmpty()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending performance tracking records found for the selected year' . ($quarter ? ' and quarter ' . $quarter : '') . '.',
                ], 404);
            }
            return redirect()->back()->with('failure', 'No pending performance tracking records found for the selected year' . ($quarter ? ' and quarter ' . $quarter : '') . '.');
        }

        // Approve all pending records
        $approvedCount = 0;
        foreach ($pendingTrackings as $tracking) {
            $tracking->sector_head_approved_at = now();
            $tracking->sector_head_approved_by = $user->id;
            $tracking->confirmation_status = 'Pending Facilitator';
            $tracking->save();
            $approvedCount++;

            // Notify Facilitator after Sector Head approval
            try {
                Notification::notifyFacilitatorAfterSectorHeadApproval($tracking);
            } catch (\Exception $e) {
                // Log error but continue processing
                \Illuminate\Support\Facades\Log::error('Notification error in approveData: ' . $e->getMessage());
            }
        }

        $message = "Successfully approved {$approvedCount} performance tracking record(s) for " . $year . ($quarter ? " Q{$quarter}" : " (All Quarters)") . ".";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'approved_count' => $approvedCount,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Facilitator confirms performance tracking and adds delivery department value
     */
    public function facilitatorConfirm(Request $request)
    {
        $user = Auth::user();

        // Only Facilitators can confirm
        if (!$user->isFacilitator()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Facilitators can confirm performance tracking.',
                ], 403);
            }
            return redirect()->back()->with('failure', 'Only Facilitators can confirm performance tracking.');
        }

        $validated = $request->validate([
            'track_id' => 'required|exists:performance_trackings,id',
            'facilitator_decision' => 'required|in:Accept,Reject',
            'delivery_department_value' => 'required_if:facilitator_decision,Accept|nullable|numeric|min:0',
            'delivery_department_remark' => 'required_if:facilitator_decision,Accept|nullable|string',
            'facilitator_rejection_reason' => 'required_if:facilitator_decision,Reject|nullable|string',
        ]);

        $tracking = PerformanceTracking::findOrFail($validated['track_id']);

        // Check if already confirmed by Coordinator
        if ($tracking->coordinator_confirmed_by) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This performance tracking has already been confirmed by Coordinator.',
                ], 400);
            }
            return redirect()->back()->with('failure', 'This performance tracking has already been confirmed by Coordinator.');
        }

        // Check if approved by Sector Head
        if (!$tracking->sector_head_approved_by) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This performance tracking must be approved by Sector Head before Facilitator can confirm.',
                ], 400);
            }
            return redirect()->back()->with('failure', 'This performance tracking must be approved by Sector Head before Facilitator can confirm.');
        }

        // Update tracking record based on decision
        $tracking->facilitator_confirmed_at = now();
        $tracking->facilitator_confirmed_by = $user->id;
        $tracking->facilitator_decision = $validated['facilitator_decision'];
        
        if ($validated['facilitator_decision'] === 'Accept') {
            $tracking->delivery_department_value = $validated['delivery_department_value'];
            $tracking->delivery_department_remark = $validated['delivery_department_remark'];
            $tracking->facilitator_rejection_reason = null;
            $tracking->confirmation_status = 'Pending Coordinator Confirmation';
        } else {
            // Reject - clear delivery department value and remark, set rejection reason
            $tracking->delivery_department_value = null;
            $tracking->delivery_department_remark = null;
            $tracking->facilitator_rejection_reason = $validated['facilitator_rejection_reason'];
            $tracking->confirmation_status = 'Rejected';
            // Clear sector head approval to allow Data Admin to edit
            $tracking->sector_head_approved_at = null;
            $tracking->sector_head_approved_by = null;
        }
        
        $tracking->save();

        // Notify based on decision
        try {
            if ($validated['facilitator_decision'] === 'Accept') {
                Notification::notifyCoordinatorAfterFacilitatorConfirmation($tracking);
            } else {
                // Notify Data Admin/Sector Head about rejection
                Notification::notifyDataAdminAfterFacilitatorRejection($tracking);
            }
        } catch (\Exception $e) {
            Log::error('Notification error in facilitatorConfirm: ' . $e->getMessage());
        }

        if ($validated['facilitator_decision'] === 'Accept') {
            $message = 'Performance tracking accepted successfully. Coordinator has been notified.';
        } else {
            $message = 'Performance tracking rejected. Data Admin has been notified to make corrections.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

}
