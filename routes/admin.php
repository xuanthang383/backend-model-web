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
        Route::get('/permissions', 'permissions');
    });

    Route::controller(ProductController::class)->prefix('products')->group(function () {
        Route::get('/', 'index')->middleware('permission:models.view');
        Route::post('/', 'store')->middleware('permission:models.add');
        Route::get('{id}', 'show')->middleware('permission:models.view');
        Route::put('{id}', 'update')->middleware('permission:models.edit');
        Route::delete('{id}', 'destroy')->middleware('permission:models.delete');
        Route::post('{id}/change-status', 'changeStatus')->middleware('permission:models.change_status');
    });

    Route::controller(ProductNameChangeRequestController::class)->prefix('/name-change-requests')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(ProductErrorReportController::class)->prefix('/products/reports')->group(function () {
        Route::get('/', 'index')->middleware('permission:productsReports.view');
        Route::patch('{report}/status', 'updateStatus')->middleware('permission:productsReports.change_status');
    });

    Route::controller(TagController::class)->prefix('/tags')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(CategoryController::class)->prefix('/categories')->group(function () {
        Route::get('/', 'index')->middleware('permission:category.view');
        Route::post('/', 'store')->middleware('permission:category.add');
        Route::get('/{id}', 'show')->middleware('permission:category.view');
        Route::put('/{id}', 'update')->middleware('permission:category.edit');
        Route::delete('/{id}', 'destroy')->middleware('permission:category.delete');
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
