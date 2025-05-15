<?php

namespace App\Http\Middleware;

use App\Http\Controllers\BaseController;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $key
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $key): Response
    {
        $user = Auth::user();
        $baseController = new BaseController();

        if (!$user) {
            return $baseController->errorResponse('Unauthenticated.', 401);
        }

        // Lấy role của user
        $role = $user->role;

        if (!$role) {
            return $baseController->errorResponse('User has no role assigned.', 403);
        }

        // Lấy tất cả permissions của role
        $permissions = $role->permissions;

        // Kiểm tra xem role có permission cần thiết không
        $hasPermission = $permissions->contains(function ($permission) use ($key) {
            return $permission->key === $key;
        });

        if (!$hasPermission) {
            return $baseController->errorResponse(
                "You do not have permission to perform this action. Required permission: $key",
                403
            );
        }

        return $next($request);
    }
}
