<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'r' => 1,
                'msg' => 'Unauthorized',
                'data' => null,
            ], 401);
        }

        return response()->json([
            'r' => 0,
            'msg' => 'User token retrieved successfully',
            'data' => $request->user()
        ]);
    }
    
    /**
     * Lấy thông tin quyền của người dùng hiện tại
     */
    public function permissions(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        
        return $this->successResponse([
            'permissions' => $user->getPermissionsJson()
        ], 'User permissions retrieved successfully');
    }
}
