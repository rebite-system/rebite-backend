<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PasswordResetCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $code = rand(100000, 999999);

        PasswordResetCode::where('email', $request->email)->delete();

        PasswordResetCode::create([
            'email' => $request->email,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);

       Mail::raw("Your ReBite password reset code is: " . $code, function ($message) use ($request) {
    $message->to($request->email)
        ->subject("ReBite Password Reset Code");
})->queue();

        return response()->json([
            'message' => 'Reset code sent to your email successfully'
        ]);
    }

    public function verifyCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required'
        ]);

        $reset = PasswordResetCode::where('email', $request->email)
            ->where('code', $request->code)
            ->where('expires_at', '>', now())
            ->first();

        if (!$reset) {
            return response()->json([
                'message' => 'Invalid or expired code'
            ], 400);
        }

        return response()->json([
            'message' => 'Code verified successfully'
        ]);
    }

    public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'password' => [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/[a-z]/',
            'regex:/[A-Z]/',
            'regex:/[0-9]/',
            'regex:/[@$!%*#?&]/'
        ]
    ]);

    $user = User::where('email', $request->email)->firstOrFail();

    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json([
        'message' => 'Password reset successfully'
    ]);
}
}
