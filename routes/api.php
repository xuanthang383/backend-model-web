<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\FavoriteProductController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HideProductController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\UserController;
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


// ✅ API Xác thực (Guest Only)
Route::middleware('guest')->group(function () {
    Route::controller(RegisteredUserController::class)->group(function () {
        Route::post('/register', 'store')->name('api.register');

    });

    Route::controller(PasswordResetLinkController::class)->group(function () {
        Route::post('/password/forgot', 'store')->name('sendResetLinkEmail');
    });
    Route::controller(ResetPasswordController::class)->group(function () {
        Route::post('/password/reset', 'resetPassword')->name('resetPassword');
    });

    Route::controller(AuthenticatedSessionController::class)->group(function () {
        Route::post('/login', 'store')->name('api.login');
        Route::post('/logout', 'destroy');
    });
});

//Route::get('/libraries', [LibraryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/platforms', [PlatformController::class, 'index']);
Route::get('/renders', [RenderController::class, 'index']);
Route::get('/materials', [MaterialController::class, 'index']);
Route::get('/colors', [ColorController::class, 'index']);
Route::post('/verify/{id}/{token}', [EmailVerificationController::class, 'verify']);

Route::controller(ProductController::class)->prefix('/products')->group(function () {
    Route::get('/', 'index');
    Route::get('/{id}', 'show');
});

// ✅ API cần bảo vệ (Yêu cầu đăng nhập)
Route::middleware(['auth:sanctum'])->group(function () {

    Route::controller(FavoriteProductController::class)->prefix('/favorite')->group(function () {
        Route::post('/toggle', 'toggleFavorite');
    });

    Route::controller(HideProductController::class)->prefix('/hide')->group(function () {
        Route::post('/toggle', 'toggleHide');
    });

    Route::controller(ChangePasswordController::class)->prefix('/password/change')->group(function () {
        Route::post('/', 'changePassword');
    });

//    Route::get('/user-token', [UserController::class, 'getUserToken']);
    Route::controller(UserController::class)->prefix('/user')->group(function () {
        Route::get('/token', 'index');
        Route::get('/permission','getPermissions');
    });

    Route::controller(LibraryController::class)->prefix("/libraries")->group(function () {
        Route::post('/', 'storeLibrary');
        Route::get('/', 'index');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'updateLibrary');
        Route::patch('/{id}', 'updateLibrary');
        Route::delete('/{id}', 'destroy');

        Route::post('/remove-model', 'removeModelFromLibrary');
        Route::post('/{id}', 'addModelToLibrary');

        Route::get('/{id}/product', 'showProduct');
    });

    Route::controller(TagController::class)->prefix('/tags')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(CategoryController::class)->prefix('/categories')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(PlatformController::class)->prefix('/platforms')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(RenderController::class)->prefix('/renders')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(MaterialController::class)->prefix('/materials')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(ColorController::class)->prefix('/colors')->group(function () {
        Route::post('/', 'store');
        Route::get('/{id}', 'show');
        Route::put('/{id}', 'update');
        Route::delete('/{id}', 'destroy');
    });

    Route::controller(ProductController::class)->prefix('/products')->group(function () {
        // Tạo mới sản phẩm
        Route::get('/user/list', 'productOfUser');
        Route::post('/', 'store');
        Route::put('/{id}', 'update');
        Route::post('/{id}/toggle-hidden', 'toggleHidden');
        Route::post('/{id}/change-status', 'changeStatus');
        Route::post('/download-model', 'downloadModelFile');
    });

    Route::controller(FileUploadController::class)->group(function () {
        Route::post('/upload-temp-images', 'uploadTempImage');
        Route::post('/upload-temp-model', 'uploadTempModel');
        Route::get('/model-file-url/{product_id}', 'getModelFileUrl');
    });
});
