<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null; // Không chuyển hướng về bất cứ đâu
    }

    protected function unauthenticated($request, array $guards)
    {
        return response()->json([
            'r' => 1,
            'msg' => 'Unauthorized - Token không hợp lệ hoặc đã hết hạn',
            'data' => null
        ], 401);
    }
}
