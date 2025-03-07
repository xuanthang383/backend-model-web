<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function getPermissions()
    {
        $userId = Auth::id();
        $user = User::with('role.permissions')->find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

     return $this->successResponse($user->getPermissionsJson());
    }
}
