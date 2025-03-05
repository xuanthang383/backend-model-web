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
        $limit = ($limit > 0) ? $limit : 10; // Đảm bảo `limit` hợp lệ

        // Kiểm tra nếu có yêu cầu sắp xếp dữ liệu
        if ($request->has('sort') && $request->has('order')) {
            $sortColumn = $request->input('sort');
            $sortOrder = $request->input('order', 'asc');
            $query = $query->orderBy($sortColumn, $sortOrder);
        }

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
            return response()->json(['error' => 'Token is missing'], 401);
        }

        $accessToken = PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        $user = $accessToken->tokenable; // Lấy user từ token

        return $user->id;
    }
}
