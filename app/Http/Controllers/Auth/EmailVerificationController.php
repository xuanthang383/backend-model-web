<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class EmailVerificationController extends BaseController
{
    /**
     * Hiển thị thông báo xác minh email
     */
    public function notice(): JsonResponse
    {
        return response()->json(['message' => 'Please verify your email.']);
    }

    /**
     * Xử lý xác minh email
     */
    public function verify($id, $token)
    {
        $user = User::where('id', $id)->where('verification_token', $token)->first();

        if (!$user) {
            return $this->errorResponse('Invalid verification link.');
        }

        // Xác nhận email
        $user->email_verified_at = now();
        $user->verification_token = null; // Xóa token sau khi xác minh
        $user->save();

        return $this->successResponse($user->email, 'Email verified successfully.');
    }

    /**
     * Gửi lại email xác thực
     */
    public function resend(Request $request, EmailService $emailService)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return $this->errorResponse('Email already verified.', 400);
        }

        // Tạo token mới nếu không có
        if (!$user->verification_token) {
            $user->verification_token = Str::random(60);
            $user->save();
        }

        // URL xác nhận email
        $verificationUrl = url("/verify/{$user->id}/{$user->verification_token}");

        // Gửi email xác nhận tài khoản
        $emailService->sendVerificationEmail($user, $verificationUrl);

        return $this->successResponse(null, 'Verification email sent successfully.');
    }
}
