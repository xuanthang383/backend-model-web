<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisteredUserController extends BaseController
{
    /**
     * Xử lý đăng ký người dùng mới.
     */
    public function store(Request $request, EmailService $emailService)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Gửi email xác nhận tài khoản
        $emailService->sendVerificationEmail($user);

        return $this->successResponse($request->email, 'Đăng ký thành công! Vui lòng kiểm tra email để xác nhận tài khoản.');
    }
}
