<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function paginateResponse($query, Request $request, $message = "Success")
    {
        // Lấy số lượng bản ghi trên mỗi trang, mặc định là 10
        $limit = (int) $request->input('limit', 10);
        $limit = ($limit > 0) ? $limit : 10; // Đảm bảo `limit` hợp lệ
    
        // Kiểm tra nếu có yêu cầu sắp xếp dữ liệu
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order', 'asc');
            $query = $query->orderBy($sortColumn, $sortOrder);
        }
    
        // Phân trang dữ liệu
        $data = $query->paginate($limit);
    
        // Trả về JSON response
        return response()->json([
            'r' => 0,
            'msg' => $message,
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
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
