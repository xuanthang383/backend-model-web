<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductNameChangeRequest;
use Illuminate\Http\Request;

class ProductNameChangeRequestController extends BaseController
{
    public function store(Request $request, Product $product)
    {
        try {
            // Thay vì sử dụng $this->authorize(), kiểm tra xác thực thủ công
            // Đảm bảo người dùng đã đăng nhập (middleware auth:sanctum đã xử lý)
            $user = auth()->user();

            // Kiểm tra sản phẩm tồn tại
            if (!$product->exists) {
                return $this->errorResponse('Product not found', 404);
            }

            // Thêm các điều kiện kiểm tra quyền khác nếu cần
            // Ví dụ: if ($user->cannot('edit', $product)) {...}

            // Validate the request
            $validated = $request->validate([
                'suggested_name' => 'required|string|max:255',
                'reason' => 'required|string'
            ]);

            // Create the name change request
            $nameChangeRequest = ProductNameChangeRequest::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'suggested_name' => $validated['suggested_name'],
                'reason' => $validated['reason'],
                'current_name' => $product->name,
                'status' => 'pending'
            ]);

            return $this->successResponse(
                $nameChangeRequest,
                'Name suggestion submitted successfully',
                201
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to submit name suggestion: ' . $e->getMessage(),
                403
            );
        }
    }
}
