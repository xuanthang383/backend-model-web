<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\FavoriteProduct;

class FavoriteProductController extends BaseController
{
    public function toggleFavorite(Request $request)
    {
        $userId = (int)$this->getUserIdFromToken($request);
        $productId = (int)$request->product_id;
        // Kiểm tra xem sản phẩm đã được yêu thích chưa
        $existingFavorite = FavoriteProduct::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
        if ($existingFavorite) {
            // Nếu đã tồn tại, xóa khỏi danh sách yêu thích
            $existingFavorite->delete();
            return $this->successResponse(null, 'Product removed from favorites');
        } else {
            // Nếu chưa tồn tại, thêm vào danh sách yêu thích
            $favorite = FavoriteProduct::create([
                'user_id' => $userId,
                'product_id' => $productId
            ]);
            return $this->successResponse($favorite, 'Product added to favorites');
        }
    }
}
