<?php

namespace App\Http\Controllers;

use App\Models\Sector;
use App\Models\SectorHead;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //
    public function index()
    {
        $users = User::all();
        $sectors = Sector::all();
        return view('pages.users.index', compact('users','sectors'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        // Validate and store user data
//        return $request;
        $user = new User();
        $user->username = $request->username;
        $user->full_name = $request->full_name;
        $user->email = $request->email;
        $user->phone_number = $request->phone_number;
        $user->role = $request->role;
        $user->password = bcrypt('JSUSER321');
        $user->full_name = $request->full_name;
        if($user->save() && $user->role==2){
            $sectorHead = new SectorHead();
            $sectorHead->sector_id = $request->sector_id;
            $sectorHead->user_id = $user->id;
            $sectorHead->date_from = $request->date_from;
            $sectorHead->date_to = $request->date_to;
            $sectorHead->save();
        }
        return back();
    }

    public function view(User $user)
    {
        return view('pages.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        // Validate and update user data
    }

    public function destroy(User $user)
    {
        // Delete the user
    }
}
