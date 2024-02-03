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
        $performanceTrackings = Sector::select('sectors.*',DB::raw("COUNT(sectors.id) as count"))
            ->join('commitments', 'sectors.id', '=', 'commitments.sector_id')
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->groupBy('sectors.id')
            ->get();

        return view('pages.users.awaiting',compact('performanceTrackings'));
    }
    public function awaitingVerificationView(Request $request,$id)
    {
        $sector = Sector::find($id);
        $performanceTrackings = Commitment::select('commitments.*',DB::raw("COUNT(commitments.id) as count"))
            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('commitments.sector_id', $id)
            ->groupBy('commitments.id')
            ->get();

        return view('pages.users.awaiting_commitment',compact('performanceTrackings','sector'));
    }
    public function awaitingVerificationCommView(Request $request,$id)
    {
        $commitment = Commitment::find($id);
        $performanceTrackings = Deliverable::select('deliverables.*',DB::raw("COUNT(deliverables.id) as count"))
//            ->join('deliverables', 'commitments.id', '=', 'deliverables.commitment_id')
            ->join('kpis', 'deliverables.id', '=', 'kpis.deliverable_id')
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('deliverables.commitment_id', $id)
            ->groupBy('deliverables.id')
            ->get();

        return view('pages.users.awaiting_deliverables',compact('performanceTrackings','commitment'));
    }

    public function awaitingVerificationDelView(Request $request,$id)
    {
        $deliverable = Deliverable::find($id);
        $kpis = Kpi::select('kpis.*',DB::raw("COUNT(kpis.id) as count"))
            ->join('performance_trackings', 'kpis.id', '=', 'performance_trackings.kpi_id')
            ->where('performance_trackings.confirmation_status', 'Not Confirmed')
            ->where('kpis.deliverable_id', $id)
            ->groupBy('kpis.id')
            ->get();

        return view('pages.users.awaiting_kpis',compact('kpis','deliverable'));
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
        $roles = ['Governor'=>'State','System Admin'=>'System','Sector Head'=>'Sector','Sector Admin'=>'Sector','Delivery Department'=>'Deliverable'];
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
            $userRole->target_entity = $roles[$request->role];
            $userRole->entity_id = $roles[$request->role]=='Sector'?$request->sector_id:1;
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
