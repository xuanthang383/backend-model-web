<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\LibraryController;
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

// ✅ API Công khai (Không cần xác thực)
Route::prefix('')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/platforms', [PlatformController::class, 'index']);
    Route::get('/renders', [RenderController::class, 'index']);
    Route::get('/colors', [ColorController::class, 'index']);
    Route::get('/materials', [MaterialController::class, 'index']);
    Route::get('/tags', [TagController::class, 'index']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
});

// ✅ API Xác thực (Guest Only)
Route::prefix('auth')->middleware('guest')->group(function () {
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('api.register');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('api.login');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
});

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
            'data' => $user // Trả về luôn object user
        ]);
    });

    Route::prefix("libraries")
        ->controller(LibraryController::class)
        ->group(function () {
            Route::get('/libraries/{id}', 'show');
            Route::post('/libraries/', 'storeLibrary');
            Route::put('/libraries/{id}', 'updateLibrary');
            Route::patch('/libraries/{id}', 'updateLibrary');
            Route::delete('/libraries/{id}', 'destroy');

            Route::post('/{id}', 'addModelToLibrary');
            Route::get('/', 'index');
            Route::get('/product/{id}', 'showProduct');
        });

    Route::post('/tags', [TagController::class, 'store']);
    Route::post('/materials', [MaterialController::class, 'store']);
    Route::post('/platforms', [PlatformController::class, 'store']);
    Route::post('/renders', [RenderController::class, 'store']);
    Route::post('/colors', [ColorController::class, 'store']);
    Route::post('/upload-temp-images', [FileUploadController::class, 'uploadTempImage']);
    Route::post('/upload-temp-model', [FileUploadController::class, 'uploadTempModel']);

    Route::prefix("/products")->controller(ProductController::class)->group(function () {
        // Tạo mới sản phẩm
        Route::post('/', 'store');
    });
});
