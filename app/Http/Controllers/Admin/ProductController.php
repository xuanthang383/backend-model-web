<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Product\ChangeStatusDTO;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Product\ChangeStatusRequest;
use App\Http\Requests\Request;
use App\Models\Product;
use Exception;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        $query = Product::with("imageFiles")
            ->with("user");

        return $this->paginateResponse($query, $request, "Success", function ($product) {
            return $product;
        });
    }

    public function changeStatus(ChangeStatusRequest $request, $id)
    {
        try {
            $requestValidate = new ChangeStatusDTO($request->validated());

            $product = Product::find($id);

            if (!$product) {
                return $this->errorResponse('Product not found', 404);
            }

            $product['status'] = $requestValidate->status;
            $product->save();

            return $this->successResponse(
                ['product' => $product],
                'Product status updated successfully'
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
