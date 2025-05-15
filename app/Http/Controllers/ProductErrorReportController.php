<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductErrorReport;
use App\Models\ErrorReason;
use Illuminate\Http\Request;

class ProductErrorReportController extends BaseController
{
    // Gửi báo cáo lỗi (user)
    public function store(Request $request, $product_id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'reason_id' => 'required|exists:error_reasons,id',
                'message' => 'nullable|string|max:1000',
            ]);

            // Tìm sản phẩm
            $product = Product::findOrFail($product_id);

            // Create the error report
            $errorReport = ProductErrorReport::create([
                'product_id' => $product_id,
                'user_id' => auth()->id(),
                'reason_id' => $validated['reason_id'],
                'current_value' => null, // Chỉ dùng cho đề xuất tên
                'suggested_value' => null, // Chỉ dùng cho đề xuất tên
                'message' => $validated['message'] ?? null,
                'status' => 'pending'
            ]);

            return $this->successResponse(
                $errorReport,
                'Báo lỗi đã được gửi thành công!',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Không thể báo lỗi: ' . $e->getMessage(),
                403
            );
        }
    }
    public function suggestName(Request $request, Product $product)
    {
        try {
            $user = auth()->user();

            // Validate request
            $validated = $request->validate([
                'suggested_name' => 'required|string|max:255',
                'message' => 'required|string|max:1000'
            ]);

            // Lấy reason_id từ bảng error_reasons với value = err_name
            $errorReason = ErrorReason::where('value', ErrorReason::ERROR_NAME)->first();

            if (!$errorReason) {
                return $this->errorResponse('Loại lỗi không tồn tại', 400);
            }

            // Tạo báo cáo lỗi với reason_id là id của loại lỗi tên sản phẩm
            $errorReport = ProductErrorReport::create([
                'product_id' => $product->id,
                'user_id' => $user->id,
                'reason_id' => $errorReason->id,
                'value' => $validated['suggested_name'],
                'message' => $validated['message'],
                'status' => 'pending'
            ]);

            return $this->successResponse(
                $errorReport,
                'Đề xuất tên mới đã được gửi thành công',
                201
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Không thể gửi đề xuất tên: ' . $e->getMessage(),
                403
            );
        }
    }
}
