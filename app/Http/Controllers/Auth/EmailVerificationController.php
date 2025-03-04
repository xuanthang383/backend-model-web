<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
}
