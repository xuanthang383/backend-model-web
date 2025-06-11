<?php

use App\Helpers\PermissionHelper;

if (!function_exists('has_permission')) {
    /**
     * Kiểm tra quyền truy cập
     *
     * @param string $function Tên chức năng
     * @param string $action Tên hành động
     * @return bool
     */
    function has_permission(string $function, string $action): bool {
        return PermissionHelper::hasPermission($function, $action);
    }
}