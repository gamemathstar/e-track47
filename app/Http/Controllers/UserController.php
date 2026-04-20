<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Deliverable;
use App\Models\FacilitatorSector;
use App\Models\Kpi;
use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\SectorHead;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use function Laravel\Prompts\password;

class UserController extends Controller
{
    //
    public function index(Request $request)
    {
        $query = User::query();

        // Search filter (name or email)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Role filter
        if ($request->filled('role')) {
            $role = $request->input('role');
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('role', $role)
                    ->where('role_status', UserRole::STATUS_ACTIVE);
            });
        }

        // Sector filter
        if ($request->filled('sector_id')) {
            $sectorId = $request->input('sector_id');
            $query->where(function ($q) use ($sectorId) {
                // For single-sector roles (Sector Head, Data Admin)
                $q->whereHas('roles', function ($roleQuery) use ($sectorId) {
                    $roleQuery->where('entity_id', $sectorId)
                        ->where('role_status', UserRole::STATUS_ACTIVE)
                        ->whereIn('role', [UserRole::ROLE_SECTOR_HEAD, UserRole::ROLE_DATA_ADMIN]);
                })
                    // For Facilitators with multiple sectors
                    ->orWhereHas('roles', function ($roleQuery) use ($sectorId) {
                        $roleQuery->where('role', UserRole::ROLE_FACILITATOR)
                            ->where('role_status', UserRole::STATUS_ACTIVE)
                            ->whereHas('facilitatorSectors', function ($fsQuery) use ($sectorId) {
                                $fsQuery->where('sector_id', $sectorId);
                            });
                    });
            });
        }

        // Paginate results - Order by role priority (Governor first), then by name
        $users = $query->orderByRaw("
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM user_roles
                    WHERE user_roles.user_id = users.id
                    AND user_roles.role = 'Governor'
                    AND user_roles.role_status = 'Active'
                ) THEN 0
                ELSE 1
            END
        ")->orderBy('full_name', 'asc')->paginate(12)->withQueryString();
        $sectors = Sector::all();

        // Get all unique roles for filter dropdown
        $roles = UserRole::where('role_status', UserRole::STATUS_ACTIVE)
            ->distinct()
            ->pluck('role')
            ->sort()
            ->values();

        return view('pages.users.index', compact('users', 'sectors', 'roles'));
    }

    public function awaitingVerification(Request $request)
    {
        $user = Auth::user();

        if ($user->isFacilitator()) {
            $performanceTrackings = $this->facilitatorAwaitingSectorsWithCounts($user);

            return view('pages.users.awaiting', compact('performanceTrackings'));
        }

        $query = Sector::select('sectors.*', DB::raw('COUNT(sectors.id) as count'))
            ->join('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed');

        $performanceTrackings = $query->groupBy('sectors.id')->get();

        return view('pages.users.awaiting', compact('performanceTrackings'));
    }

    /**
     * Sectors that have at least one performance tracking row awaiting facilitator action
     * (Sector Head approved, facilitator not yet confirmed), with per-sector row counts.
     */
    private function facilitatorAwaitingSectorsWithCounts(User $user): \Illuminate\Support\Collection
    {
        $assignedSectorIds = null;
        if (!$user->canAccessAllSectors()) {
            $assignedSectorIds = $user->getAssignedSectorIds();
            if (empty($assignedSectorIds)) {
                return collect();
            }
        }

        $countQuery = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('commitments', 'commitments.id', '=', 'deliverables.commitment_id')
            ->when($assignedSectorIds !== null, function ($q) use ($assignedSectorIds) {
                $q->whereIn('commitments.sector_id', $assignedSectorIds);
            })
            ->where(function ($q) use ($user) {
                $q->where(function ($w) {
                    $w->whereNotNull('performance_trackings.sector_head_approved_by')
                        ->whereNull('performance_trackings.facilitator_confirmed_by')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                })->orWhere(function ($w) use ($user) {
                    $w->whereNotNull('performance_trackings.facilitator_confirmed_by')
                        ->where('performance_trackings.facilitator_confirmed_by', $user->id)
                        ->where('performance_trackings.facilitator_decision', 'Reject')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                });
            })
            ->groupBy('commitments.sector_id')
            ->select('commitments.sector_id as sector_id', DB::raw('COUNT(*) as count'));

        $rows = $countQuery->get();
        if ($rows->isEmpty()) {
            return collect();
        }

        $sectors = Sector::whereIn('id', $rows->pluck('sector_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($sectors) {
            $sector = $sectors->get($row->sector_id);
            if (!$sector) {
                return null;
            }
            $sector->count = (int)$row->count;

            return $sector;
        })->filter()->values();
    }

    public function awaitingVerificationView(Request $request, $id)
    {
        $user = Auth::user();
        $sector = Sector::find($id);

        if ($user->isFacilitator()) {
            if (!$user->canAccessAllSectors()) {
                $assigned = $user->getAssignedSectorIds();
                if (empty($assigned) || !in_array((int)$id, $assigned, true)) {
                    $performanceTrackings = collect();

                    return view('pages.users.awaiting_commitment', compact('performanceTrackings', 'sector'));
                }
            }
            $performanceTrackings = $this->facilitatorAwaitingCommitmentsWithCounts($user, (int)$id);

            return view('pages.users.awaiting_commitment', compact('performanceTrackings', 'sector'));
        }

        $query = Commitment::select('commitments.*', DB::raw('COUNT(commitments.id) as count'))
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('commitments.sector_id', $id)
            ->where('performance_trackings.confirmation_status', 'Not Confirmed');

        $performanceTrackings = $query->groupBy('commitments.id')->get();

        return view('pages.users.awaiting_commitment', compact('performanceTrackings', 'sector'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Commitment>
     */
    private function facilitatorAwaitingCommitmentsWithCounts(User $user, int $sectorId): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('commitments', 'commitments.id', '=', 'deliverables.commitment_id')
            ->where('commitments.sector_id', $sectorId)
            ->where(function ($q) use ($user) {
                $q->where(function ($w) {
                    $w->whereNotNull('performance_trackings.sector_head_approved_by')
                        ->whereNull('performance_trackings.facilitator_confirmed_by')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                })->orWhere(function ($w) use ($user) {
                    $w->whereNotNull('performance_trackings.facilitator_confirmed_by')
                        ->where('performance_trackings.facilitator_confirmed_by', $user->id)
                        ->where('performance_trackings.facilitator_decision', 'Reject')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                });
            })
            ->groupBy('commitments.id')
            ->select('commitments.id as commitment_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $commitments = Commitment::whereIn('id', $rows->pluck('commitment_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($commitments) {
            $commitment = $commitments->get($row->commitment_id);
            if (!$commitment) {
                return null;
            }
            $commitment->count = (int)$row->count;

            return $commitment;
        })->filter()->values();
    }

    public function awaitingVerificationCommView(Request $request, $id)
    {
        $user = Auth::user();
        $commitment = Commitment::find($id);

        if ($user->isFacilitator()) {
            if ($commitment && !$user->canAccessAllSectors()) {
                $assigned = $user->getAssignedSectorIds();
                if (empty($assigned) || !in_array((int)$commitment->sector_id, $assigned, true)) {
                    $performanceTrackings = collect();

                    return view('pages.users.awaiting_deliverables', compact('performanceTrackings', 'commitment'));
                }
            }
            $performanceTrackings = $this->facilitatorAwaitingDeliverablesWithCounts($user, (int)$id);

            return view('pages.users.awaiting_deliverables', compact('performanceTrackings', 'commitment'));
        }

        $query = Deliverable::select('deliverables.*', DB::raw('COUNT(deliverables.id) as count'))
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('deliverables.commitment_id', $id)
            ->where('performance_trackings.confirmation_status', 'Not Confirmed');

        $performanceTrackings = $query->groupBy('deliverables.id')->get();

        return view('pages.users.awaiting_deliverables', compact('performanceTrackings', 'commitment'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Deliverable>
     */
    private function facilitatorAwaitingDeliverablesWithCounts(User $user, int $commitmentId): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->where('deliverables.commitment_id', $commitmentId)
            ->where(function ($q) use ($user) {
                $q->where(function ($w) {
                    $w->whereNotNull('performance_trackings.sector_head_approved_by')
                        ->whereNull('performance_trackings.facilitator_confirmed_by')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                })->orWhere(function ($w) use ($user) {
                    $w->whereNotNull('performance_trackings.facilitator_confirmed_by')
                        ->where('performance_trackings.facilitator_confirmed_by', $user->id)
                        ->where('performance_trackings.facilitator_decision', 'Reject')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                });
            })
            ->groupBy('deliverables.id')
            ->select('deliverables.id as deliverable_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $deliverables = Deliverable::whereIn('id', $rows->pluck('deliverable_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($deliverables) {
            $deliverable = $deliverables->get($row->deliverable_id);
            if (!$deliverable) {
                return null;
            }
            $deliverable->count = (int)$row->count;

            return $deliverable;
        })->filter()->values();
    }

    public function awaitingVerificationDelView(Request $request, $id)
    {
        $user = Auth::user();
        $deliverable = Deliverable::with('commitment')->find($id);

        if ($user->isFacilitator()) {
            if ($deliverable && $deliverable->commitment && !$user->canAccessAllSectors()) {
                $assigned = $user->getAssignedSectorIds();
                $sectorId = (int)$deliverable->commitment->sector_id;
                if (empty($assigned) || !in_array($sectorId, $assigned, true)) {
                    $kpis = collect();

                    return view('pages.users.awaiting_kpis', compact('kpis', 'deliverable', 'user'));
                }
            }
            $kpis = $this->facilitatorAwaitingKpisWithCounts($user, (int)$id);

            return view('pages.users.awaiting_kpis', compact('kpis', 'deliverable', 'user'));
        }

        $query = Kpi::select('kpis.*', DB::raw('COUNT(kpis.id) as count'))
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('kpis.deliverable_id', $id)
            ->whereNotNull('performance_trackings.sector_head_approved_by')
            ->whereNull('performance_trackings.facilitator_confirmed_by')
            ->whereNull('performance_trackings.coordinator_confirmed_by');

        $kpis = $query->groupBy('kpis.id')->get();

        return view('pages.users.awaiting_kpis', compact('kpis', 'deliverable', 'user'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Kpi>
     */
    private function facilitatorAwaitingKpisWithCounts(User $user, int $deliverableId): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('kpis.deliverable_id', $deliverableId)
            ->where(function ($q) use ($user) {
                $q->where(function ($w) {
                    $w->whereNotNull('performance_trackings.sector_head_approved_by')
                        ->whereNull('performance_trackings.facilitator_confirmed_by')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                })->orWhere(function ($w) use ($user) {
                    $w->whereNotNull('performance_trackings.facilitator_confirmed_by')
                        ->where('performance_trackings.facilitator_confirmed_by', $user->id)
                        ->where('performance_trackings.facilitator_decision', 'Reject')
                        ->whereNull('performance_trackings.coordinator_confirmed_by');
                });
            })
            ->groupBy('kpis.id')
            ->select('kpis.id as kpi_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $kpis = Kpi::whereIn('id', $rows->pluck('kpi_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($kpis) {
            $kpi = $kpis->get($row->kpi_id);
            if (!$kpi) {
                return null;
            }
            $kpi->count = (int)$row->count;

            return $kpi;
        })->filter()->values();
    }

    public function coordinatorFinalReview(Request $request)
    {
        $user = Auth::user();
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            abort(403);
        }
        $performanceTrackings = $this->coordinatorPendingSectorsWithCounts();

        return view('pages.users.coordinator_final_review', compact('performanceTrackings'));
    }

    public function coordinatorFinalReviewSector(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            abort(403);
        }
        $sector = Sector::findOrFail($id);
        $performanceTrackings = $this->coordinatorPendingCommitmentsWithCounts((int)$id);

        return view('pages.users.coordinator_final_commitments', compact('sector', 'performanceTrackings'));
    }

    public function coordinatorFinalReviewCommitment(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            abort(403);
        }
        $commitment = Commitment::findOrFail($id);
        $performanceTrackings = $this->coordinatorPendingDeliverablesWithCounts((int)$id);

        return view('pages.users.coordinator_final_deliverables', compact('commitment', 'performanceTrackings'));
    }

    public function coordinatorFinalReviewDeliverable(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->isCoordinator() && !$user->isDeputyCoordinator()) {
            abort(403);
        }
        $deliverable = Deliverable::with('commitment.sector')->findOrFail($id);
        $kpis = Kpi::where('deliverable_id', $id)
            ->with([
                'performanceTracking.sectorHeadApprovedBy:id,full_name',
                'performanceTracking.facilitatorConfirmedBy:id,full_name',
            ])
            ->get();

        $coordinatorTrackDetails = [];
        foreach ($kpis as $kpi) {
            foreach ($kpi->performanceTracking as $track) {
                if ($track->isAwaitingCoordinatorFinalApproval()) {
                    $coordinatorTrackDetails[$track->id] = [
                        'kpi_name' => $kpi->kpi,
                        'target_value' => $kpi->target_value,
                        'unit_of_measurement' => $kpi->unit_of_measurement,
                        'quarter' => $track->quarter,
                        'year' => $track->year,
                        'milestone' => $track->milestone,
                        'tracking_date' => $track->tracking_date ? $track->tracking_date->format('Y-m-d') : null,
                        'actual_value' => $track->actual_value,
                        'remarks' => $track->remarks,
                        'sector_head_name' => $track->sectorHeadApprovedBy?->full_name,
                        'sector_head_at' => $track->sector_head_approved_at ? $track->sector_head_approved_at->format('Y-m-d H:i') : null,
                        'facilitator_name' => $track->facilitatorConfirmedBy?->full_name,
                        'facilitator_at' => $track->facilitator_confirmed_at ? $track->facilitator_confirmed_at->format('Y-m-d H:i') : null,
                        'facilitator_decision' => $track->facilitator_decision,
                        'delivery_department_value' => $track->delivery_department_value,
                        'delivery_department_remark' => $track->delivery_department_remark,
                    ];
                }
            }
        }

        return view('pages.users.coordinator_final_kpis', compact('deliverable', 'kpis', 'coordinatorTrackDetails'));
    }

    /**
     * @return \Illuminate\Support\Collection<int, Sector>
     */
    private function coordinatorPendingSectorsWithCounts(): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('commitments', 'commitments.id', '=', 'deliverables.commitment_id')
            ->whereNotNull('performance_trackings.sector_head_approved_by')
            ->where('performance_trackings.facilitator_decision', 'Accept')
            ->whereNotNull('performance_trackings.facilitator_confirmed_by')
            ->whereNull('performance_trackings.coordinator_confirmed_by')
            ->groupBy('commitments.sector_id')
            ->select('commitments.sector_id as sector_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $sectors = Sector::whereIn('id', $rows->pluck('sector_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($sectors) {
            $sector = $sectors->get($row->sector_id);
            if (!$sector) {
                return null;
            }
            $sector->count = (int)$row->count;

            return $sector;
        })->filter()->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Commitment>
     */
    private function coordinatorPendingCommitmentsWithCounts(int $sectorId): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('commitments', 'commitments.id', '=', 'deliverables.commitment_id')
            ->where('commitments.sector_id', $sectorId)
            ->whereNotNull('performance_trackings.sector_head_approved_by')
            ->where('performance_trackings.facilitator_decision', 'Accept')
            ->whereNotNull('performance_trackings.facilitator_confirmed_by')
            ->whereNull('performance_trackings.coordinator_confirmed_by')
            ->groupBy('commitments.id')
            ->select('commitments.id as commitment_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $commitments = Commitment::whereIn('id', $rows->pluck('commitment_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($commitments) {
            $commitment = $commitments->get($row->commitment_id);
            if (!$commitment) {
                return null;
            }
            $commitment->count = (int)$row->count;

            return $commitment;
        })->filter()->values();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Deliverable>
     */
    private function coordinatorPendingDeliverablesWithCounts(int $commitmentId): \Illuminate\Support\Collection
    {
        $rows = DB::table('performance_trackings')
            ->join('kpis', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->join('deliverables', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->where('deliverables.commitment_id', $commitmentId)
            ->whereNotNull('performance_trackings.sector_head_approved_by')
            ->where('performance_trackings.facilitator_decision', 'Accept')
            ->whereNotNull('performance_trackings.facilitator_confirmed_by')
            ->whereNull('performance_trackings.coordinator_confirmed_by')
            ->groupBy('deliverables.id')
            ->select('deliverables.id as deliverable_id', DB::raw('COUNT(*) as count'))
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $deliverables = Deliverable::whereIn('id', $rows->pluck('deliverable_id'))->get()->keyBy('id');

        return $rows->map(function ($row) use ($deliverables) {
            $deliverable = $deliverables->get($row->deliverable_id);
            if (!$deliverable) {
                return null;
            }
            $deliverable->count = (int)$row->count;

            return $deliverable;
        })->filter()->values();
    }

    public function create()
    {
        return view('users.create');
    }


    public function updatePerformance(Request $request)
    {
        // Validate the request data
        $request->validate([
            'delivery_department_value' => 'required',
            'delivery_department_remark' => 'nullable',
            'confirmation_status' => 'required|in:Confirmed,Not Confirmed',
            'performance_id' => 'required|exists:performance_trackings,id',
        ]);

        // Find the performance tracking record
        $performance = PerformanceTracking::findOrFail($request->input('performance_id'));

        // Update the performance tracking record
        $performance->update([
            'delivery_department_value' => $request->input('delivery_department_value'),
            'delivery_department_remark' => $request->input('delivery_department_remark'),
            'confirmation_status' => $request->input('confirmation_status'),
        ]);

        // You can return a response if needed
        return response()->json(['message' => 'Performance tracking updated successfully']);
    }

    public function store(Request $request)
    {
        // Normalize facilitator sector payload before validation.
        if (
            $request->input('role') === UserRole::ROLE_FACILITATOR
            && !$request->filled('sector_ids')
            && $request->filled('sector_id')
        ) {
            $request->merge([
                'sector_ids' => [(int)$request->input('sector_id')],
            ]);
        }

        // Validate the request
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . ($request->id ?? 'NULL'),
            'phone_number' => 'nullable|string|max:20',
            'role' => 'required|in:' . implode(',', [
                    UserRole::ROLE_GOVERNOR,
                    UserRole::ROLE_SYSTEM_ADMIN,
                    UserRole::ROLE_SECTOR_HEAD,
                    UserRole::ROLE_SECTOR_ADMIN, // Deprecated
                    UserRole::ROLE_DATA_ADMIN,
                    UserRole::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
                    UserRole::ROLE_COORDINATOR,
                    UserRole::ROLE_DEPUTY_COORDINATOR,
                    UserRole::ROLE_FACILITATOR,
                ]),
            'sector_id' => 'nullable|required_if:role,' . UserRole::ROLE_SECTOR_HEAD . ',' . UserRole::ROLE_SECTOR_ADMIN . ',' . UserRole::ROLE_DATA_ADMIN . '|exists:sectors,id',
            'sector_ids' => 'nullable|required_if:role,' . UserRole::ROLE_FACILITATOR . '|array',
            'sector_ids.*' => 'exists:sectors,id',
        ], [
            'sector_id.required_if' => 'Please select a sector for this role.',
            'sector_id.exists' => 'The selected sector does not exist.',
            'sector_ids.required_if' => 'Please select at least one sector for Facilitator role.',
            'sector_ids.array' => 'Sectors must be provided as an array.',
            'sector_ids.*.exists' => 'One or more selected sectors do not exist.',
        ]);

        if (($validated['role'] ?? null) === UserRole::ROLE_FACILITATOR) {
            $validated['sector_ids'] = array_values(array_unique(array_map('intval', $validated['sector_ids'] ?? [])));
        }

        try {
            // Create or update user
            if (isset($request->id)) {
                $user = User::findOrFail($request->id);
            } else {
                $user = new User();
                $user->password = bcrypt('123456'); // Default password
            }

            $user->full_name = $validated['full_name'];
            $user->email = $validated['email'];
            $user->phone_number = $validated['phone_number'] ?? null;

            if ($user->save()) {
                // Get role to entity mapping
                $roleMapping = UserRole::getRoleToEntityMapping();
                $targetEntity = $roleMapping[$validated['role']] ?? null;

                if (!$targetEntity) {
                    return back()->with('failure', 'Invalid role specified.');
                }

                // Determine entity_id
                $entityId = 0;
                if (in_array($validated['role'], [UserRole::ROLE_SECTOR_HEAD, UserRole::ROLE_SECTOR_ADMIN, UserRole::ROLE_DATA_ADMIN])) {
                    $entityId = $validated['sector_id'] ?? 0;
                }
                // Facilitators use facilitator_sectors pivot table (entity_id = 0)
                // Coordinators and Deputy Coordinators have entity_id = 0 (access all sectors)

                // Check if user already has an active role
                $existingRole = UserRole::where('user_id', $user->id)
                    ->where('role_status', UserRole::STATUS_ACTIVE)
                    ->first();

                if ($existingRole) {
                    // Revoke existing active role and remove facilitator sectors
                    if ($existingRole->role === UserRole::ROLE_FACILITATOR) {
                        FacilitatorSector::where('user_role_id', $existingRole->id)->delete();
                    }
                    $existingRole->revoke();
                }

                // Create new role assignment
                $userRole = new UserRole();
                $userRole->user_id = $user->id;
                $userRole->role = $validated['role'];
                $userRole->target_entity = $targetEntity;
                $userRole->entity_id = $entityId;
                $userRole->role_status = UserRole::STATUS_ACTIVE;
                $userRole->save();

                // For Facilitators, save multiple sectors in pivot table
                if ($validated['role'] === UserRole::ROLE_FACILITATOR && !empty($validated['sector_ids'])) {
                    foreach ($validated['sector_ids'] as $sectorId) {
                        FacilitatorSector::create([
                            'user_role_id' => $userRole->id,
                            'sector_id' => $sectorId,
                        ]);
                    }
                }

                $message = isset($request->id) ? 'User updated successfully.' : 'User created successfully.';
                return back()->with('success', $message);
            }

            return back()->with('failure', 'Failed to save user.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred: ' . $e->getMessage());
        }
    }

    public function view(User $user)
    {
        $sectors = Sector::all();
        return view('pages.users.show', compact('user', 'sectors'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Validate and update user data
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|exists:users,id',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password',
        ], [
            'confirm_password.same' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 6 characters.',
        ]);

        try {
            $user = User::findOrFail($validated['id']);

            if ($validated['password'] === $validated['confirm_password']) {
                $user->password = bcrypt($validated['password']);
                $user->save();
                return back()->with('success', 'Password changed successfully.');
            }

            return back()->with('failure', 'Password confirmation does not match.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred while changing the password: ' . $e->getMessage());
        }
    }

    public function uploadPhoto(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'img_url' => 'required|file|mimes:jpg,png,jpeg|max:2048'
        ]);

        try {
            $user = User::findOrFail($validated['user_id']);
            $file = $request->file('img_url');
            $fileName = $user->id . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/users'), $fileName);

            $user->image_url = $fileName;
            $user->save();

            return back()->with('success', 'Profile photo updated successfully.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred while uploading the photo: ' . $e->getMessage());
        }
    }

    public function destroy(User $user)
    {
        // Delete the user
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, User $user)
    {
        // Normalize facilitator sector payload before validation.
        if (
            $request->input('role') === UserRole::ROLE_FACILITATOR
            && !$request->filled('sector_ids')
            && $request->filled('sector_id')
        ) {
            $request->merge([
                'sector_ids' => [(int)$request->input('sector_id')],
            ]);
        }

        $validated = $request->validate([
            'role' => 'required|in:' . implode(',', [
                    UserRole::ROLE_GOVERNOR,
                    UserRole::ROLE_SYSTEM_ADMIN,
                    UserRole::ROLE_SECTOR_HEAD,
                    UserRole::ROLE_SECTOR_ADMIN, // Deprecated
                    UserRole::ROLE_DATA_ADMIN,
                    UserRole::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
                    UserRole::ROLE_COORDINATOR,
                    UserRole::ROLE_DEPUTY_COORDINATOR,
                    UserRole::ROLE_FACILITATOR,
                ]),
            'sector_id' => 'nullable|required_if:role,' . UserRole::ROLE_SECTOR_HEAD . ',' . UserRole::ROLE_SECTOR_ADMIN . ',' . UserRole::ROLE_DATA_ADMIN . '|exists:sectors,id',
            'sector_ids' => 'nullable|required_if:role,' . UserRole::ROLE_FACILITATOR . '|array',
            'sector_ids.*' => 'exists:sectors,id',
        ], [
            'sector_id.required_if' => 'Please select a sector for this role.',
            'sector_id.exists' => 'The selected sector does not exist.',
            'sector_ids.required_if' => 'Please select at least one sector for Facilitator role.',
            'sector_ids.array' => 'Sectors must be provided as an array.',
            'sector_ids.*.exists' => 'One or more selected sectors do not exist.',
        ]);

        if (($validated['role'] ?? null) === UserRole::ROLE_FACILITATOR) {
            $validated['sector_ids'] = array_values(array_unique(array_map('intval', $validated['sector_ids'] ?? [])));
        }

        try {
            // Get role to entity mapping
            $roleMapping = UserRole::getRoleToEntityMapping();
            $targetEntity = $roleMapping[$validated['role']] ?? null;

            if (!$targetEntity) {
                return back()->with('failure', 'Invalid role specified.');
            }

            // Determine entity_id
            $entityId = 0;
            if (in_array($validated['role'], [UserRole::ROLE_SECTOR_HEAD, UserRole::ROLE_SECTOR_ADMIN, UserRole::ROLE_DATA_ADMIN])) {
                $entityId = $validated['sector_id'] ?? 0;
            }
            // Facilitators use facilitator_sectors pivot table (entity_id = 0)
            // Coordinators and Deputy Coordinators have entity_id = 0 (access all sectors)

            // Get existing active roles to clean up facilitator sectors
            $existingActiveRoles = UserRole::where('user_id', $user->id)
                ->where('role_status', UserRole::STATUS_ACTIVE)
                ->get();

            // Delete facilitator sectors for existing facilitator roles
            foreach ($existingActiveRoles as $existingRole) {
                if ($existingRole->role === UserRole::ROLE_FACILITATOR) {
                    FacilitatorSector::where('user_role_id', $existingRole->id)->delete();
                }
            }

            // Revoke all existing active roles
            UserRole::where('user_id', $user->id)
                ->where('role_status', UserRole::STATUS_ACTIVE)
                ->update(['role_status' => UserRole::STATUS_REVOKED]);

            // Create new role assignment
            $userRole = new UserRole();
            $userRole->user_id = $user->id;
            $userRole->role = $validated['role'];
            $userRole->target_entity = $targetEntity;
            $userRole->entity_id = $entityId;
            $userRole->role_status = UserRole::STATUS_ACTIVE;
            $userRole->save();

            // For Facilitators, save multiple sectors in pivot table
            if ($validated['role'] === UserRole::ROLE_FACILITATOR && !empty($validated['sector_ids'])) {
                foreach ($validated['sector_ids'] as $sectorId) {
                    FacilitatorSector::create([
                        'user_role_id' => $userRole->id,
                        'sector_id' => $sectorId,
                    ]);
                }
            }

            return back()->with('success', 'User role updated successfully.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Revoke user role
     */
    public function revokeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:user_roles,id',
        ]);

        try {
            $userRole = UserRole::where('id', $validated['role_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($userRole->isRevoked()) {
                return back()->with('failure', 'This role is already revoked.');
            }

            $userRole->revoke();

            return back()->with('success', 'User role revoked successfully.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred: ' . $e->getMessage());
        }
    }

    /**
     * Reactivate a revoked role
     */
    public function reactivateRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:user_roles,id',
        ]);

        try {
            $userRole = UserRole::where('id', $validated['role_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            if ($userRole->isActive()) {
                return back()->with('failure', 'This role is already active.');
            }

            // Revoke all other active roles for this user
            UserRole::where('user_id', $user->id)
                ->where('role_status', UserRole::STATUS_ACTIVE)
                ->where('id', '!=', $userRole->id)
                ->update(['role_status' => UserRole::STATUS_REVOKED]);

            // Activate the selected role
            $userRole->activate();

            return back()->with('success', 'User role reactivated successfully.');
        } catch (\Exception $e) {
            return back()->with('failure', 'An error occurred: ' . $e->getMessage());
        }
    }
}
