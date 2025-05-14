<?php

use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductErrorReportController;
use App\Http\Controllers\Admin\ProductNameChangeRequestController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ErrorReasonController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::controller(AuthenticatedSessionController::class)->group(function () {
    Route::post('/login', 'store')->name('admin.api.login');
    Route::post('/logout', 'destroy');
});

// ✅ API cần bảo vệ (Yêu cầu đăng nhập)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::controller(UserController::class)->prefix('/user')->group(function () {
        Route::get('/token', 'index');
    });

    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::controller(ProductErrorReportController::class)->prefix('/reports')->group(function () {
            Route::get('/', 'index');
            Route::patch('{report}/status', 'updateStatus');
        });

        Route::controller(ProductNameChangeRequestController::class)->prefix('/name-change-requests')->group(function () {
            Route::get('/', 'index');
        });

        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('{id}', 'show');
        Route::put('{id}', 'update');
        Route::delete('{id}', 'destroy');
        Route::post('{id}/change-status', 'changeStatus');
    });

    Route::controller(TagController::class)->prefix('/tags')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(CategoryController::class)->prefix('/categories')->group(function () {
        Route::get('/', 'index');
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(PlatformController::class)->prefix('/platforms')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(RenderController::class)->prefix('/renders')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(MaterialController::class)->prefix('/materials')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(ColorController::class)->prefix('/colors')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(FileUploadController::class)->group(function () {
        Route::post('/upload-temp-images', 'uploadTempImage');
        Route::post('/upload-temp-model', 'uploadTempModel');
    });

    Route::controller(ErrorReasonController::class)->prefix('/error-reasons')->group(function () {
        Route::get('/', 'index');
    });
});
