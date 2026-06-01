<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        return response()->json([
            'data' => auth()->user()
        ]);
    }

    public function update(Request $r)
    {
        $user = auth()->user();

        $data = $r->validate([

            'name' =>
                'required|string|max:255',

            'email' =>
                'required|email|unique:users,email,' . $user->id,

            'phone' =>
                'nullable|string|max:20',

            'location' =>
                'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            'cuisine' =>
                'nullable|string|max:255',

            'card_last4' =>
                'nullable|string|size:4',

            'vodafone_number' =>
                'nullable|string|max:20',

            'instapay_address' =>
                'nullable|string|max:255',

        ]);

        $user->update($data);

        return response()->json([
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    public function changePassword(Request $r)
    {
        $r->validate([

            'current_password' =>
                'required',

            'new_password' =>
                'required|min:8',

            'new_password_confirmation' =>
                'required|same:new_password',

        ]);

        $user = auth()->user();

        if (
            !Hash::check(
                $r->current_password,
                $user->password
            )
        ) {

            return response()->json([
                'message' =>
                    'Current password is incorrect'
            ], 400);
        }

        $user->update([

            'password' =>
                bcrypt($r->new_password)

        ]);

        return response()->json([
            'message' =>
                'Password updated successfully'
        ]);
    }
}