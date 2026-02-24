<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'رمز عبور یا ایمیل کاربری اشتباه وارد شده'], 400);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken
        ]);
    }

    public function profile()
    {
        return response()->json([
            'user' => auth()->user()
        ]);
    }

    public function logout()
    {
        auth()->logout();
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'in:user,venue_admin,super_admin'
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Hash::make($request->password),
            'phone' => $request->phone,
            'role' => $request->role ?? 'user',
        ]);

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('api')->plainTextToken
        ], 201);
    }
}
