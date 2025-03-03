<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
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
    public function verify(EmailVerificationRequest $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return $this->errorResponse($request->email, 'Library details with one level children');

        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return $this->successResponse($request->email, 'Email verified successfully.');

    }
}
