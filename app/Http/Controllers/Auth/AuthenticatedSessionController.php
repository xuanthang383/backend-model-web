<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

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
                'r' => 1,
                'message' => 'The provided credentials are incorrect.'
            ], 401);
        }

        // 3️⃣ Lấy thông tin user đã đăng nhập
        $user = Auth::user();

        if (is_null($user->email_verified_at)) {
            return response()->json([
                'r' => 1,
                'message' => 'Your email address has not been verified.',
                'error_code' => 'EMAIL_NOT_VERIFIED'
            ], 403); // 403 Forbidden
        }

        // 4️⃣ XÓA tất cả token cũ của user trước khi tạo token mới
        $user->tokens()->delete();

        // 5️⃣ Tạo token API mới sử dụng Laravel Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6️⃣ Trả về thông tin user + token mới
        return response()->json([
            'r' => 0,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : null,
                'permissions' => $user->getPermissionsJson()
            ]
        ]);
    }

    public function destroy(Request $request)
    {
        Log::info("Destroy function called");

        if ($request->header('Authorization')) {
            Log::info("Authorization Header: " . $request->header('Authorization'));
        }

        if ($request->user()) {
            Log::info("User authenticated, deleting tokens...");
            $request->user()->tokens()->delete();
        } else {
            Log::info("User not authenticated, possible invalid token.");
        }

        return response()->json(['message' => 'Logged out successfully']);
    }


    public function firstAccess(Request $request)
    {
        // Kiểm tra xem user có đang đăng nhập không
        if (Auth::check()) {
            return response()->json([
                'message' => 'User already authenticated',
                'user' => Auth::user(),
            ]);
        }

        // Nếu chưa đăng nhập, có thể trả về thông tin hệ thống hoặc tạo guest token
        return response()->json([
            'message' => 'Welcome to our API',
            'system' => [
                'app_name' => config('app.name'),
                'version' => '1.0.0',
                'timezone' => config('app.timezone'),
            ]
        ]);
    }
}
