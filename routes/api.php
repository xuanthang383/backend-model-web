<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

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


// ✅ API Xác thực (Guest Only)
Route::middleware('guest')->group(function () {
    Route::controller(RegisteredUserController::class)->group(function () {
        Route::post('/register', 'store')->name('api.register');
    });

    Route::controller(ForgotPasswordController::class)->group(function () {
        Route::post('/forgot-password', 'store')->name('forgotPassword');
    });
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::post('/password/reset', 'store')->name('reset');
    });

    Route::controller(AuthenticatedSessionController::class)->group(function () {
        Route::post('/login', 'store')->name('api.login');
        Route::post('/logout', 'destroy');
    });
});

// ✅ API cần bảo vệ (Yêu cầu đăng nhập)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

//    Route::get('/user-token', [UserController::class, 'getUserToken']);
    Route::controller(UserController::class)->prefix('/user-token')->group(function () {
        Route::get('/', 'index');
    });

    Route::controller(LibraryController::class)->prefix("/libraries")->group(function () {
        Route::post('/', 'storeLibrary');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'updateLibrary');
        Route::patch('/{id}', 'updateLibrary');
        Route::delete('/{id}', 'destroy');

        Route::post('/{id}', 'addModelToLibrary');
        Route::get('/product/{id}', 'showProduct');
    });

    Route::controller(TagController::class)->prefix('/tags')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(CategoryController::class)->prefix('/categories')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(PlatformController::class)->prefix('/platforms')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(RenderController::class)->prefix('/renders')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(MaterialController::class)->prefix('/materials')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(ColorController::class)->prefix('/colors')->group(function () {
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(ProductController::class)->prefix('/products')->group(function () {
        // Tạo mới sản phẩm
        Route::post('/', 'store');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');

        Route::post('/{id}/change-status', 'changeStatus');
    });

    Route::controller(FileUploadController::class)->group(function () {
        Route::post('/upload-temp-images', 'uploadTempImage');
        Route::post('/upload-temp-model', 'uploadTempModel');
    });
});
