<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Auth;

class PermissionHelper
{
    /**
     * Kiểm tra xem user hiện tại có quyền thực hiện hành động không
     *
     * @param string $function Tên chức năng (ví dụ: 'category')
     * @param string $action Tên hành động (ví dụ: 'view', 'add', 'edit', 'delete')
     * @return bool
     */
    public static function hasPermission(string $function, string $action): bool
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }
        
        $permissions = $user->role->permissions;
        
        return $permissions->contains(function ($permission) use ($function, $action) {
            return $permission->function === $function && $permission->action === $action;
        });
    }
    
    /**
     * Kiểm tra quyền và ném ra ngoại lệ nếu không có quyền
     *
     * @param string $function Tên chức năng
     * @param string $action Tên hành động
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public static function checkPermission(string $function, string $action): void
    {
        if (!self::hasPermission($function, $action)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                "You do not have permission to $action $function."
            );
        }
    }
}