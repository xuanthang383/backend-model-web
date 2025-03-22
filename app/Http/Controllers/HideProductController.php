<?php

namespace App\Http\Controllers;

use App\Models\HideProduct;
use Illuminate\Http\Request;

class HideProductController extends BaseController
{
    public function toggleHide(Request $request)
    {
        $userId = (int)auth()->id();

        $productId = (int)$request->product_id;
        // Kiểm tra xem sản phẩm đã ẩn chưa
        $existingHide = HideProduct::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
        if ($existingHide) {
            // Nếu đã ẩn, cho hiện trở lại
            $existingHide->delete();
            return $this->successResponse(null, 'Product removed from hides');
        } else {
            // Nếu chưa tồn tại, thêm vào danh sách ẩn
            $favorite = HideProduct::create([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return $this->successResponse($favorite, 'Product added to hides');
        }
    }
}
