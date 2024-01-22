<?php

namespace App\Http\Controllers;

use App\Models\PerformanceTracking;
use App\Models\Sector;
use App\Models\SectorHead;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\Request;
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
        $performanceTrackings = PerformanceTracking::with(['kpi.deliverable.commitment.sector'])
            ->where(function ($query) {
                $query->whereNull('confirmation_status')
                    ->orWhere('confirmation_status', '')
                    ->orWhere('confirmation_status', 'Not Confirmed');
            })
            ->get();

        return view('pages.users.awaiting',compact('performanceTrackings'));
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
        // Validate and store user data
//        return $request;
        if (isset($request->id))
            $user = User::find($request->id);
        else
            $user = new User();
        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        if (!isset($request->id))
            $user->password = bcrypt('JSUSER321');

        if ($user->save()) {
            $userRole = UserRole::where(['user_id' => $user->id])->first();
            if (is_null($userRole))
                $userRole = new UserRole();

            $userRole->user_id = $user->id;
            $userRole->role = $request->role;
            $userRole->target_entity = 'Sector';
            $userRole->entity_id = $request->sector_id;
            $userRole->role_status = 'Active';
            $userRole->save();
        }
        return back();
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
        $user = User::find($request->id);
        if ($request->password == $request->confirm_password) {
            $user->password = bcrypt($request->password);
            $user->save();
        }

        return back();
    }

    public function destroy(User $user)
    {
        // Delete the user
    }
}
