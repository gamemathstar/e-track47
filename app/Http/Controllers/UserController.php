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
            $query->where(function($q) use ($sectorId) {
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
        $performanceTrackings = Sector::select('sectors.*', DB::raw("COUNT(sectors.id) as count"))
            ->join('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->groupBy('sectors.id')
            ->get();

        return view('pages.users.awaiting', compact('performanceTrackings'));
    }

    public function awaitingVerificationView(Request $request, $id)
    {
        $sector = Sector::find($id);
        $performanceTrackings = Commitment::select('commitments.*', DB::raw("COUNT(commitments.id) as count"))
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('commitments.sector_id', $id)
            ->groupBy('commitments.id')
            ->get();

        return view('pages.users.awaiting_commitment', compact('performanceTrackings', 'sector'));
    }

    public function awaitingVerificationCommView(Request $request, $id)
    {
        $commitment = Commitment::find($id);
        $performanceTrackings = Deliverable::select('deliverables.*', DB::raw("COUNT(deliverables.id) as count"))
//            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('deliverables.commitment_id', $id)
            ->groupBy('deliverables.id')
            ->get();

        return view('pages.users.awaiting_deliverables', compact('performanceTrackings', 'commitment'));
    }

    public function awaitingVerificationDelView(Request $request, $id)
    {
        $deliverable = Deliverable::find($id);
        $kpis = Kpi::select('kpis.*', DB::raw("COUNT(kpis.id) as count"))
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('kpis.deliverable_id', $id)
            ->groupBy('kpis.id')
            ->get();

        return view('pages.users.awaiting_kpis', compact('kpis', 'deliverable'));
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
        ], [
            'sector_id.required_if' => 'Please select a sector for this role.',
            'sector_id.exists' => 'The selected sector does not exist.',
        ]);

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
