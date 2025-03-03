<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\EmailService;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends BaseController
{
    public function forgotPassword(Request $request, EmailService $emailService)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['msg' => 'Email không tồn tại!'], 404);
        }

        $token = Password::createToken($user);

        // Gửi email đặt lại mật khẩu
        $emailService->sendResetPasswordEmail($user, $token);

        return response()->json(['msg' => 'Email đặt lại mật khẩu đã được gửi!']);
    }
}
