<?php

namespace App\Http\Controllers;

use App\DTO\Library\LibraryDTO;
use App\Http\Requests\Library\LibraryRequest;
use App\Models\Library;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LibraryController extends BaseController
{
    /**
     * Liệt kê tất cả các thư viện của user hiện tại (có phân trang).
     * GET /api/libraries
     */
    public function index(LibraryRequest $request)
    {
        try {
            $validData = new LibraryDTO($request->validated());

            $libraryModel = Library::where('user_id', Auth::id());

            if ($validData->parent_id) {
                $libraryModel->where('parent_id', $validData->parent_id);
            } else {
                $libraryModel->whereNull('parent_id');
            }

            $libraries = $libraryModel->get();
            return $this->successResponse($libraries, 'List of libraries');
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    // Show thư viện
    public function show($id)
    {
        // Tìm thư viện theo id và đảm bảo thuộc về user hiện tại
        $library = Library::where('user_id', Auth::id())
            ->where('id', $id)
            ->with([
                "children" => function ($query) {
                    $query->where("user_id", Auth::id());
                },
            ])
            ->first();

        if (!$library) {
            return $this->errorResponse('Library not found or not owned by you', 404);
        }

        return $this->successResponse($library, 'Library details with one level children');
    }


    public function storeLibrary(Request $request)
    {
        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:libraries,id',
        ]);

        $userId = Auth::id();
        // Tạo mới thư viện
        $library = Library::create([
            'user_id' => $userId,
            'parent_id' => $validatedData['parent_id'] ?? null,
            'name' => $validatedData['name'],
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

        // Kiểm tra: thư viện này không được dùng làm thư viện cha (không có thư viện con)
        if (Library::where('parent_id', $library->id)->exists()) {
            return $this->errorResponse('Cannot add model to a library that has children', 409);
        }

        // Validate dữ liệu, đảm bảo product_id tồn tại
        $validatedData = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $productId = $validatedData['product_id'];

        // Kiểm tra nếu product đã tồn tại trong thư viện thì không thêm nữa
        if ($library->products()->where('products.id', $productId)->exists()) {
            return $this->errorResponse('Model already exists in the library', 409);
        }

        // Attach product vào library qua bảng pivot
        $library->products()->attach($productId);

        // Lấy lại model vừa được thêm để trả về (tuỳ chọn)
        $product = Product::find($productId);

        return $this->successResponse($product, 'Model added to library successfully');
    }


    /**
     * Hiển thị chi tiết 1 thư viện (tuỳ chọn).
     * GET /api/libraries/{id}
     */
    public function showProduct($id)
    {
        $library = Library::with("products.imageFiles")
            ->find($id);

        if (!$library) {
            return response()->json(['message' => 'Library not found'], 404);
        }

        $library->products->each(function ($product) {
            $product->thumbnail = $product->imageFiles->first(function ($file) {
                return $file->pivot->is_thumbnail == 1;
            });
        });

        return $this->successResponse($library, 'Library details in');
    }


    /**
     * Cập nhật thông tin 1 thư viện (tuỳ chọn).
     * PUT/PATCH /api/libraries/{id}
     */
    public function updateLibrary(Request $request, $id)
    {
        $userId = Auth::id() ?: 3;

        // Lấy thư viện của user hiện tại
        $library = Library::where('user_id', $userId)->find($id);
        if (!$library) {
            return $this->errorResponse($library, 'Cannot find library');
        }
        // Validate dữ liệu cập nhật
        $validatedData = $request->validate([
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|integer|exists:libraries,id',
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
