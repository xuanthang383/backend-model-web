<?php

namespace App\Http\Controllers\Admin;

use App\DTO\Product\ChangeStatusDTO;
use App\DTO\Product\CreateDTO;
use App\Http\Controllers\BaseController;
use App\Http\Requests\Product\ChangeStatusRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Models\Product;
use Exception;
use Illuminate\Http\Request;

class ProductController extends BaseController
{
    public function index(Request $request)
    {
        \DB::enableQueryLog();
        $query = Product::with("imageFiles")
            ->with("user")
            ->orderBy("products.created_at", "desc");

        return $this->paginateResponse($query, $request, "Success", function ($product) {
            return $product;
        });
    }

    public function store(StoreProductRequest $request)
    {
        try {
            $productResp = (new Product())->createProduct(new CreateDTO($request->validated()));

            return $this->successResponse(
                ['product' => $productResp],
                'Product created successfully with colors, materials, and tags',
                201
            );
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
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
