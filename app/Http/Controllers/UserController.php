<?php

namespace App\Http\Controllers;

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

    public function create()
    {
        return view('users.create');
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
