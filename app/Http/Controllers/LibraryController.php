<?php

namespace App\Http\Controllers;

use App\DTO\Library\LibraryDTO;
use App\Http\Requests\Library\LibraryRequest;
use App\Models\FavoriteProduct;
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

        $userId = (int)$this->getUserIdFromToken($request);
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
        $userId = (int)$this->getUserIdFromToken($request);

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
        // Nếu sản phẩm đã nằm trong danh sách yêu thích, thì xóa khỏi đó
        FavoriteProduct::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete();

        // Lấy lại model vừa được thêm để trả về (tuỳ chọn)
        $product = Product::find($productId);

        return $this->successResponse($product, 'Model added to library successfully');
    }

    /**
     * Xóa một model khỏi thư viện của user hiện tại.
     * DELETE /api/libraries/{libraryId}/models/{productId}
     */
    public function removeModelFromLibrary(Request $request)
    {
        $userId = (int)$this->getUserIdFromToken($request);

        // Validate dữ liệu đầu vào
        $validatedData = $request->validate([
            'library_id' => 'required|integer|exists:libraries,id',
            'product_id' => 'required|integer|exists:products,id',
        ]);

        $libraryId = $validatedData['library_id'];
        $productId = $validatedData['product_id'];

        // Kiểm tra thư viện có thuộc về người dùng hiện tại không
        $library = Library::where('user_id', $userId)->findOrFail($libraryId);

        // Kiểm tra model có tồn tại trong thư viện không
        if (!$library->products()->where('products.id', $productId)->exists()) {
            return $this->errorResponse('Model not found in the library', 404);
        }

        // Xóa product khỏi library qua bảng pivot
        $library->products()->detach($productId);

        return $this->successResponse(null, 'Model removed from library successfully');
    }


    /**
     * Hiển thị chi tiết 1 thư viện (tuỳ chọn).
     * GET /api/libraries/{id}
     */
    public function showProduct(Request $request, $id)
    {
        $userId = (int)$this->getUserIdFromToken($request);
        // Tìm Library của user, kèm danh sách sản phẩm và libraries của sản phẩm đó
        $library = Library::where('user_id', $userId)
            ->with([
                'products' => function ($query) use ($userId) {
                    $query->with([
                        'imageFiles',
                        'libraries' => function ($query) use ($userId) {
                            $query->wherePivot('libraries.user_id', $userId); // Chỉ lấy library của user
                        },
                        'favorites' => function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                        'hides' => function ($query) use ($userId) {
                            $query->where('user_id', $userId);
                        },
                    ]);
                }
            ])
            ->find($id);

        if (!$library) {
            return response()->json(['message' => 'Library not found'], 404);
        }

        // Duyệt danh sách sản phẩm để xử lý dữ liệu
        $library->products->each(function ($product) {
            // Lấy ảnh thumbnail
            $product->thumbnail = $product->imageFiles->first(function ($file) {
                return $file->pivot->is_thumbnail == 1;
            });

            // Ẩn thông tin không cần thiết
            $product->makeHidden("imageFiles", "pivot");
            if ($product->thumbnail) {
                $product->thumbnail->makeHidden("pivot");
            }
        });

        return $this->successResponse($library, 'Library details in');
    }


    /**
     * Cập nhật thông tin 1 thư viện (tuỳ chọn).
     * PUT/PATCH /api/libraries/{id}
     */
    public function updateLibrary(Request $request, $id)
    {
        $userId = (int)$this->getUserIdFromToken($request);

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
    public function destroy(Request $request, $id)
    {
        $userId = (int)$this->getUserIdFromToken($request);

        // Tìm thư viện
        $library = Library::where('user_id', $userId)->findOrFail($id);

        // Xoá
        $library->delete();

        return $this->successResponse(null, 'Library deleted successfully');
    }
}
