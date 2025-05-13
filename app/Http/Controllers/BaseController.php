<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;


class BaseController extends Controller
{
    public function paginateResponse($query, Request $request, $message = "Success", callable $callback = null)
    {
        // Lấy số lượng bản ghi trên mỗi trang, mặc định là 10
        $limit = (int)$request->input('limit', 10);
        $page = (int)$request->input('page', 1);
        
        // Kiểm tra nếu có yêu cầu sắp xếp dữ liệu
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order', 'asc');
            $query = $query->orderBy($sortColumn, $sortOrder);
        }

        // Xử lý trường hợp đặc biệt khi limit = -999 (lấy tất cả dữ liệu)
        if ($limit === -999) {
            // Lấy tổng số bản ghi
            $total = $query->count();
            
            // Lấy tất cả dữ liệu
            $allItems = $query->get();
            
            // Nếu có callback xử lý dữ liệu, áp dụng vào collection
            if ($callback) {
                $allItems->transform($callback);
            }
            
            // Trả về JSON response với cấu trúc giống phân trang
            return response()->json([
                'r' => 0,
                'msg' => $message,
                'data' => $allItems,
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $total,
                    'total' => $total,
                ]
            ]);
        }
        
        // Đảm bảo `limit` hợp lệ cho trường hợp thông thường
        $limit = ($limit > 0) ? $limit : 10;
        
        // Phân trang dữ liệu
        $data = $query->paginate($limit, ['*'], 'page', $page);

        // Nếu có callback xử lý dữ liệu, áp dụng vào collection
        if ($callback) {
            $data->getCollection()->transform($callback);
        }

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

    /**
     * @param $data mixed
     * @param string|null $message string
     * @param int|null $code numeric
     * @param int|null $r numeric
     * @return JsonResponse
     */
    public function successResponse(mixed $data, string|null $message = "Success", int|null $code = 200, int|null $r = 0)
    {
        return response()->json([
            'r' => $r,
            'msg' => $message,
            'data' => $data
        ], $code);
    }

    public function errorResponse($message = "Error", $code = 500, $r = 1)
    {
        return response()->json([
            'r' => $r,
            'msg' => $message,
            'data' => null
        ], $code);
    }

    public function getUserIdFromToken(Request $request)
    {
        $token = $request->bearerToken(); // Lấy token từ header "Authorization"

        if (!$token) {
            return null; // Trả về null thay vì response JSON
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null; // Trả về null nếu token không hợp lệ
        }

        $user = $accessToken->tokenable; // Lấy user từ token

        return $user ? (int)$user->id : null; // Trả về user ID hoặc null
    }

}
