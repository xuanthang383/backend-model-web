<?php

namespace App\Http\Controllers;

use App\Models\HideProduct;
use Illuminate\Http\Request;

class HideProductController extends BaseController
{
    public function toggleHide(Request $request)
    {
        $userId = (int)auth()->id();

        $userId = 1;

        $productId = (int)$request->product_id;
        // Kiểm tra xem sản phẩm đã được yêu thích chưa
        $existingHide = HideProduct::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
        if ($existingHide) {
            // Nếu đã tồn tại, xóa khỏi danh sách yêu thích
            $existingHide->delete();
            return $this->successResponse(null, 'Product removed from hides');
        } else {
            // Nếu chưa tồn tại, thêm vào danh sách yêu thích
            $favorite = HideProduct::create([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return $this->successResponse($favorite, 'Product added to hides');
        }
    }
}
