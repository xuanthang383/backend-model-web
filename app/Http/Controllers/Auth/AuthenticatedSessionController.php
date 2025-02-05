<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request)
    {
        // 1️⃣ Xác thực dữ liệu đầu vào
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // 2️⃣ Thử đăng nhập
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // 3️⃣ Lấy thông tin user đã đăng nhập
        $user = Auth::user();

        // 4️⃣ Tạo token API sử dụng Laravel Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5️⃣ Trả về thông tin user + token
        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]
        ]);
    }

    /**
     * Logout API (Hủy token).
     */
    public function destroy(Request $request)
    {
        // 6️⃣ Hủy token hiện tại
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }
    // public function firstAccess(Request $request)
    // {
    //     // Kiểm tra xem user có đang đăng nhập không
    //     if (Auth::check()) {
    //         return response()->json([
    //             'message' => 'User already authenticated',
    //             'user' => Auth::user(),
    //         ]);
    //     }

    //     // Nếu chưa đăng nhập, có thể trả về thông tin hệ thống hoặc tạo guest token
    //     return response()->json([
    //         'message' => 'Welcome to our API',
    //         'system' => [
    //             'app_name' => config('app.name'),
    //             'version' => '1.0.0',
    //             'timezone' => config('app.timezone'),
    //         ]
    //     ]);
    // }
}
