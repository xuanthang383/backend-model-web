<?php

namespace App\Helpers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Kiểm tra xem user hiện tại có quyền thực hiện hành động không
     *
     * @param string $key Mã năng (ví dụ: 'category.view')
     * @return bool
     */
    public static function hasPermission(string $key): bool
    {
        $user = Auth::user();

        if (!$user || !$user->role) {
            return false;
        }

        $permissions = $user->role->permissions;

        return $permissions->contains(function ($permission) use ($key) {
            return $permission->key === $key;
        });
    }

    /**
     * Kiểm tra quyền và ném ra ngoại lệ nếu không có quyền
     *
     * @param string $key Mã năng (ví dụ: 'category.view')
     * @throws AuthorizationException
     */
    public static function checkPermission(string $key): void
    {
        if (!self::hasPermission($key)) {
            throw new AuthorizationException(
                "You do not have permission to $key."
            );
        }
    }
}
