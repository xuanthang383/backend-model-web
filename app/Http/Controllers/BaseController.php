<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BaseController extends Controller
{
    public function paginateResponse($query, Request $request, $message = "Success")
    {
        $limit = $request->input('limit', 10);
        $data = $query->paginate($limit);

        return response()->json([
            'r' => 0,
            'msg' => $message,
            'data' => $data->items(),
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

