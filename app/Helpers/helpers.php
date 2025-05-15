<?php

use App\Helpers\PermissionHelper;

if (!function_exists('has_permission')) {
    /**
     * Kiểm tra xem người dùng hiện tại có quyền thực hiện hành động không
     *
     * @param string $function Tên chức năng (ví dụ: 'category')
     * @param string $action Tên hành động (ví dụ: 'view', 'add', 'edit', 'delete')
     * @return bool
     */
    function has_permission(string $function, string $action): bool
    {
        return PermissionHelper::hasPermission($function, $action);
    }
}

if (!function_exists('check_permission')) {
    /**
     * Kiểm tra quyền và ném ra ngoại lệ nếu không có quyền
     *
     * @param string $function Tên chức năng
     * @param string $action Tên hành động
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    function check_permission(string $function, string $action): void
    {
        PermissionHelper::checkPermission($function, $action);
    }
}