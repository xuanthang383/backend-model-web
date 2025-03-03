<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckToken
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'r' => 1,
                'msg' => 'Token hết hạn hoặc không hợp lệ, vui lòng đăng nhập lại',
                'data' => null,
            ], 401);
        }

        return $next($request);
    }
}
