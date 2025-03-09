<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class ResetPasswordController extends BaseController
{
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? $this->successResponse('Password reset successfully!')
            : $this->errorResponse('Invalid token or email');
    }

    public function checkToken($token)
    {
        $resetRecord = DB::table('password_reset_tokens')->where('token', $token)->first();

        if (!$resetRecord) {
            return $this->errorResponse('Invalid or expired token');

        }

        return $this->successResponse('Success', $resetRecord->email);
    }
}

