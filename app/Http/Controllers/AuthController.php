<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',

            'email' => 'required|email|unique:users',

            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/'
            ],

            'role' => 'required|in:restaurant,charity,donor'
        ]);

        $user = User::create([
            'name' => $request->name,

            'email' => $request->email,

            'password' => Hash::make($request->password),

            'role' => $request->role,

            'is_approved' =>
                in_array($request->role, ['restaurant', 'charity'])
                    ? false
                    : true,
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user
        ]);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',

            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        if (
            $user->role !== 'admin' &&
            $user->is_banned
        ) {

            return response()->json([
                'message' => 'Your request was rejected'
            ], 403);
        }

        if (
            in_array($user->role, ['restaurant', 'charity']) &&
            !$user->is_approved
        ) {

            return response()->json([
                'message' => 'Your account is pending admin approval'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login success',

            'token' => $token,

            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    protected function redirectTo($request)
    {
        if ($request->expectsJson()) {
            return null;
        }

        return null;
    }
}