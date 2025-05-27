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
        // Không cần khai báo hàm helper ở đây nữa
        // Đã di chuyển sang file app/Helpers/functions.php
    }
}
