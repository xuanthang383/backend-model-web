<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;


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

        $permissions = User::with('role.permissions')->find(Auth::id()) ? User::with('role.permissions')->find(Auth::id())->getPermissionsJson() : [];

        return response()->json([
            'r' => 0,
            'msg' => 'User token retrieved successfully',
            'data' => $request->user(),
            'role' => $user->role ? $user->role->name : null,
            'permissions' => $permissions,
            'avatar' => $user->avatar,
            'avatar_url' => $user->avatar_url // Sử dụng accessor avatar_url
        ]);
    }


    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
        ]);

        if ($validator->fails()) {
            return $this->errorResponse($validator->errors(), 422);
        }

        // Update user information
        $user->update($validator->validated());

        return $this->successResponse($user, 'User information updated successfully');
    }

    public function getPermissions(Request $request)
    {
        $userId = (int)$this->getUserIdFromToken($request);
        $user = User::with('role.permissions')->find($userId);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return $this->successResponse($user->getPermissionsJson());
    }
    
    /**
     * Lấy URL avatar của user hiện tại
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvatarUrl(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return $this->errorResponse('Unauthorized', 401);
        }
        
        if (!$user->avatar) {
            return $this->errorResponse('User does not have an avatar', 404);
        }
        
        try {
            // Trả về URL công khai từ S3
            $url = Storage::disk('s3')->url("avatars/{$user->avatar}");
            
            return $this->successResponse([
                'avatar_url' => $url
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating avatar URL: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'avatar' => $user->avatar
            ]);
            return $this->errorResponse('Failed to generate avatar URL', 500);
        }
    }
}
