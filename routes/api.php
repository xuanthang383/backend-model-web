<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
// Route::post('/login', function () {
//     return ['dungtq test post hihi' => app()->version()];
// });

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('api.register');

Route::post('/login', [AuthenticatedSessionController::class, 'store'])
    ->middleware('guest')
    ->name('api.login');

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth:sanctum'); // ✅ Dùng Sanctum nếu API dùng token

// Route::post('/forgots-password', [PasswordResetLinkController::class, 'store'])
//     ->middleware('guest')
//     ->name('password.email');

// Route::post('/upload-3d', [FileUploadController::class, 'upload3DModel']);
// ->middleware('auth:sanctum'); // ✅ Dùng Sanctum nếu API dùng token
Route::post('/upload-temp-images', [FileUploadController::class, 'uploadTempImage']);
Route::post('/upload-temp-model', [FileUploadController::class, 'uploadTempModel']);
Route::post('/products', [ProductController::class, 'store']);

// Route::get('/access', [AuthenticatedSessionController::class, 'firstAccess']);
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
