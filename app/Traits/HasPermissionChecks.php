<?php

namespace App\Traits;

use App\Helpers\PermissionHelper;
use Illuminate\Auth\Access\AuthorizationException;

trait HasPermissionChecks
{
    /**
     * Kiểm tra quyền và xử lý ngoại lệ
     *
     * @param string $function Tên chức năng
     * @param string $action Tên hành động
     * @return bool True nếu có quyền, false nếu không có quyền
     */
    protected function checkPermissionOrFail(string $function, string $action): bool
    {
        try {
            PermissionHelper::checkPermission($function, $action);
            return true;
        } catch (AuthorizationException $e) {
            return false;
        }
    }
    
    /**
     * Kiểm tra xem người dùng có quyền không
     *
     * @param string $function Tên chức năng
     * @param string $action Tên hành động
     * @return bool
     */
    protected function hasPermission(string $function, string $action): bool
    {
        return PermissionHelper::hasPermission($function, $action);
    }
}