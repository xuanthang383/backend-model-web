<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\PlatformController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\TagController;
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

// ✅ API công khai (Không cần auth)
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/platforms', [PlatformController::class, 'index']);
Route::get('/renders', [RenderController::class, 'index']);
Route::get('/colors', [ColorController::class, 'index']);
Route::get('/materials', [MaterialController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('guest');
// ✅ API xác thực người dùng
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest')->name('api.register');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest')->name('api.login');
Route::post('/cate', [CategoryController::class, 'store'])->middleware('guest')->name('api.cate');
Route::post('/products', [ProductController::class, 'store']);
// ✅ API cần bảo vệ (Yêu cầu đăng nhập)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/user-token', function (Request $request) {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'r' => 1,
                'msg' => 'Unauthorized',
                'data' => null,
            ], 401);
        }
    
        return response()->json([
            'r' => 0,
            'msg' => 'User token retrieved successfully',
            'data' => [
            'token' => $request->bearerToken(),
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                    'email_verified_at' => $user->email_verified_at,
                ],
            ],
        ]);
    });

    // Upload file (Chỉ user đăng nhập mới có quyền)
    Route::post('/upload-temp-images', [FileUploadController::class, 'uploadTempImage']);
    Route::post('/upload-temp-model', [FileUploadController::class, 'uploadTempModel']);

    // Tạo mới sản phẩm
    // Route::post('/products', [ProductController::class, 'store']);
});
