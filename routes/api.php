<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
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
use App\Http\Controllers\TestFileUploadController;
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

    // Tạo mới thư viện
    Route::post('/libraries', [LibraryController::class, 'storeLibrary']);

    // Thêm model vào thư viện với tham số libraryId
    Route::post('/libraries/{id}', [LibraryController::class, 'addModelToLibrary']);

    // (Tuỳ chọn) Xem danh sách thư viện của user hiện tại
    Route::get('/libraries', [LibraryController::class, 'index']);

    // (Tuỳ chọn) Xem chi tiết của 1 thư viện
    Route::get('/libraries/{id}', [LibraryController::class, 'show']);

    // (Tuỳ chọn) Xem danh sách sản phẩm trong thư viện
    Route::get('/libraries/product/{id}', [LibraryController::class, 'showProduct']);

    // (Tuỳ chọn) Cập nhật thư viện
    Route::put('/libraries/{id}', [LibraryController::class, 'updateLibrary']);
    Route::patch('/libraries/{id}', [LibraryController::class, 'updateLibrary']);

    // (Tuỳ chọn) Xóa thư viện
    Route::delete('/libraries/{id}', [LibraryController::class, 'destroy']);

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

    Route::post('/tags', [TagController::class, 'store']);
    Route::post('/materials', [MaterialController::class, 'store']);
    Route::post('/platforms', [PlatformController::class, 'store']);
    Route::post('/renders', [RenderController::class, 'store']);
    Route::post('/colors', [ColorController::class, 'store']);
    Route::post('/upload-temp-images', [FileUploadController::class, 'uploadTempImage']);
    Route::post('/upload-temp-model', [FileUploadController::class, 'uploadTempModel']);

    // Tạo mới sản phẩm
    Route::post('/products', [ProductController::class, 'store']);
});
