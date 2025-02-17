<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function paginateResponse($query, Request $request, $message = "Success")
    {
        $limit = $request->input('limit', 10);
        $data = $query->paginate($limit);

        // Thêm URL đầy đủ vào `image_path` và `file_path`
        $data->getCollection()->transform(function ($product) {
            $product->image_path = $product->image_path ? env('URL_IMAGE') . $product->image_path : null;
            $product->file_path = $product->file_path ? env('URL_IMAGE') . $product->file_path : null;
            return $product;
        });

        return response()->json([
            'r' => 0,
            'msg' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ],
            'links' => [
                'first' => $data->url(1),
                'last' => $data->url($data->lastPage()),
                'prev' => $data->previousPageUrl(),
                'next' => $data->nextPageUrl(),
            ]
        ]);
    }


    public function successResponse($data, $message = "Success")
    {
        return response()->json([
            'r' => 0,
            'msg' => $message,
            'data' => $data
        ]);
    }

    public function errorResponse($message = "Error", $code = 400)
    {
        return response()->json([
            'r' => 1,
            'msg' => $message,
            'data' => null
        ], $code);
    }
}
