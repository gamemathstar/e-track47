<?php

namespace App\Http\Controllers;

use App\Models\Commitment;
use App\Models\Deliverable;
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
    public function index()
    {
        $users = User::all();
        $sectors = Sector::all();
        return view('pages.users.index', compact('users', 'sectors'));
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
                UserRole::ROLE_SECTOR_ADMIN,
                UserRole::ROLE_DELIVERY_DEPARTMENT,
            ]),
            'sector_id' => 'required_if:role,' . UserRole::ROLE_SECTOR_HEAD . ',' . UserRole::ROLE_SECTOR_ADMIN . '|exists:sectors,id',
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
                $user->password = bcrypt('JSUSER321'); // Default password
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
                if (in_array($validated['role'], [UserRole::ROLE_SECTOR_HEAD, UserRole::ROLE_SECTOR_ADMIN, UserRole::ROLE_FACILITATOR])) {
                    $entityId = $validated['sector_id'] ?? 0;
                }
                // Coordinators and Deputy Coordinators have entity_id = 0 (access all sectors)

                // Check if user already has an active role
                $existingRole = UserRole::where('user_id', $user->id)
                    ->where('role_status', UserRole::STATUS_ACTIVE)
                    ->first();

                if ($existingRole) {
                    // Revoke existing active role
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
                UserRole::ROLE_SECTOR_ADMIN,
                UserRole::ROLE_DELIVERY_DEPARTMENT, // For backward compatibility
                UserRole::ROLE_COORDINATOR,
                UserRole::ROLE_DEPUTY_COORDINATOR,
                UserRole::ROLE_FACILITATOR,
            ]),
            'sector_id' => 'required_if:role,' . UserRole::ROLE_SECTOR_HEAD . ',' . UserRole::ROLE_SECTOR_ADMIN . ',' . UserRole::ROLE_FACILITATOR . '|exists:sectors,id',
        ], [
            'sector_id.required_if' => 'Please select a sector for this role.',
            'sector_id.exists' => 'The selected sector does not exist.',
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
            if (in_array($validated['role'], [UserRole::ROLE_SECTOR_HEAD, UserRole::ROLE_SECTOR_ADMIN, UserRole::ROLE_FACILITATOR])) {
                $entityId = $validated['sector_id'] ?? 0;
            }
            // Coordinators and Deputy Coordinators have entity_id = 0 (access all sectors)

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
