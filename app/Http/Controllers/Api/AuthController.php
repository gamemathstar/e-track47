<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //

    // UserController.php

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'full_name' => $validatedData['full_name'],
            'email' => $validatedData['email'],
            'phone_number' => $validatedData['phone_number'],
            'password' => bcrypt($validatedData['password']),
        ]);

        $token = $user->createToken('AppName')->accessToken;

        return response()->json(['token' => $token], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            $userRole = $user->role();
            $token = $user->createToken('eTrackerX8nE@9')->accessToken;
            $usr = [
                'id'=>$user->id,
                'name'=>$user->full_name,
                'email'=>$user->email,
                'phone'=>$user->phone_number,
                'rank'=>$userRole->role,
                'sector'=>$userRole->target_entity=="Sector"?$userRole->sector:"",
                'photo'=>asset('uploads/users/'.$user->image_url),
                'token'=>$token
            ];
            return response()->json(['success'=>true,'message'=>'successful login','data'=>$usr], 200);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json(['message' => 'Successfully logged out'], 200);
    }
}
