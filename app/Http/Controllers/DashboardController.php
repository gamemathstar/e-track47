<?php

namespace App\Http\Controllers;

use App\Models\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    //
    public function __construct()
    {
        $this->middleware("auth");
    }


    public function index()
    {
        $user = Auth::user();
        if(!$user->isGovernor() && !$user->isDeliveryDepartment()){
            $userRole = UserRole::where(['user_id' => $user->id])->first();
            return redirect(route('sectors.view',[$userRole->entity_id]));
        }
        return view('pages.dashboard.index');
    }
}
