<?php

namespace App\Http\Middleware;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            abort(response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401));
        }
        return null; // Không chuyển hướng về bất cứ đâu
    }

    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthorized - Token không hợp lệ hoặc đã hết hạn',
            $guards
        );
    }
}
