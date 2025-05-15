<?php

namespace App\Providers;

use App\Helpers\PermissionHelper;
use Illuminate\Support\ServiceProvider;

class PermissionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Đăng ký helper function toàn cục
        if (!function_exists('has_permission')) {
            function has_permission(string $function, string $action): bool {
                return PermissionHelper::hasPermission($function, $action);
            }
        }
    }
}
