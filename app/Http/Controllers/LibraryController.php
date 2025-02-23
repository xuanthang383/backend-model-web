<?php

namespace App\Http\Controllers;

use App\Models\Library;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends BaseController
{
    /**
     * Liệt kê tất cả các thư viện của user hiện tại (có phân trang).
     * GET /api/libraries
     */
    public function storeLibrary(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:libraries,id',
        ]);

        $userId = Auth::id();
        dd(Auth::id());

        // Tạo mới thư viện
        $library = Library::create([
            'user_id'     => $userId,
            'parent_id'   => $validatedData['parent_id'] ?? null,
            'name'        => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
        ]);

        return $this->successResponse($library, 'Library created successfully');
    }
    /**
     * Tạo mới một thư viện cho user hiện tại.
     * POST /api/libraries
     */
    public function addModelToLibrary(Request $request, $libraryId)
    {
        $userId = Auth::id();
        // Kiểm tra thư viện có thuộc về người dùng hiện tại không
        $library = Library::where('user_id', $userId)->findOrFail($libraryId);

        // Validate dữ liệu, đảm bảo product_id tồn tại
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = $validatedData['product_id'];

        // Kiểm tra nếu model (product) đã tồn tại trong thư viện
        if ($library->products()->where('products.id', $productId)->exists()) {
            return $this->errorResponse('Model already exists in the library', 409);
        }

        // Attach product vào library qua bảng pivot
        $library->products()->attach($productId);

        // (Tuỳ chọn) Lấy lại model vừa được thêm để trả về
        $product = Product::find($productId);

        return $this->successResponse($product, 'Model added to library successfully');
    }

    /**
     * Hiển thị chi tiết 1 thư viện (tuỳ chọn).
     * GET /api/libraries/{id}
     */
    public function show($id)
    {
        $userId = Auth::id();
        
        // Lấy thư viện theo id và user_id (đảm bảo user chỉ xem được thư viện của chính họ)
        $library = Library::where('user_id', $userId)->findOrFail($id);

        return $this->successResponse($library, 'Library details');
    }

    /**
     * Cập nhật thông tin 1 thư viện (tuỳ chọn).
     * PUT/PATCH /api/libraries/{id}
     */
    public function update(Request $request, $id)
    {
        $userId = Auth::id();

        // Lấy thư viện của user hiện tại
        $library = Library::where('user_id', $userId)->findOrFail($id);

        // Validate dữ liệu cập nhật
        $validatedData = $request->validate([
            'name'        => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|integer|exists:libraries,id',
        ]);

        $library->update($validatedData);

        return $this->successResponse($library, 'Library updated successfully');
    }

    /**
     * Xoá 1 thư viện (tuỳ chọn).
     * DELETE /api/libraries/{id}
     */
    public function destroy($id)
    {
        $userId = Auth::id();

        // Tìm thư viện
        $library = Library::where('user_id', $userId)->findOrFail($id);

        // Xoá
        $library->delete();

        return $this->successResponse(null, 'Library deleted successfully');
    }
}
