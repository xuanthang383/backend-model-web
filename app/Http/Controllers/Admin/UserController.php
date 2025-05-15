<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\BaseController;
use App\Models\User;
use App\Models\Role;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

        try {
            // Kiểm tra quyền xem danh sách người dùng
            // PermissionHelper::checkPermission('users.view');

            $query = User::with('role');

            // Filter by name if provided
            if ($request->has('name')) {
                $name = $request->input('name');
                $query->where('name', 'LIKE', "%{$name}%");
            }

            // Filter by email if provided
            if ($request->has('email')) {
                $email = $request->input('email');
                $query->where('email', 'LIKE', "%{$email}%");
            }

            // Filter by role if provided
            if ($request->has('role_id')) {
                $roleId = $request->input('role_id');
                $query->where('role_id', $roleId);
            }

            // Filter by status (email verification) if provided
            if ($request->has('status')) {
                $status = $request->input('status');
                if ($status === 'verified') {
                    $query->whereNotNull('email_verified_at');
                } elseif ($status === 'unverified') {
                    $query->whereNull('email_verified_at');
                }
            }

            $users = $query->get();

            return response()->json([
                'r' => 0,
                'msg' => 'Users retrieved successfully',
                'data' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => [
                            'id' => $user->role->id,
                            'name' => $user->role->name
                        ],
                        'avatar' => $user->avatar,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at
                    ];
                })
            ]);

        } catch (AuthorizationException $e) {
            return response()->json([
                'r' => 1,
                'msg' => $e->getMessage(),
                'data' => null,
            ], 403);
        } catch (Exception $e) {
            return response()->json([
                'r' => 1,
                'msg' => 'Failed to retrieve users: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
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

    /**
     * Display a listing of users
     */
    public function list(Request $request)
    {
        try {
            // Kiểm tra quyền xem danh sách người dùng
            // PermissionHelper::checkPermission('users.view');

            $query = User::with('role');

            // Filter by name if provided
            if ($request->has('name')) {
                $name = $request->input('name');
                $query->where('name', 'LIKE', "%{$name}%");
            }

            // Filter by email if provided
            if ($request->has('email')) {
                $email = $request->input('email');
                $query->where('email', 'LIKE', "%{$email}%");
            }

            // Filter by role if provided
            if ($request->has('role_id')) {
                $roleId = $request->input('role_id');
                $query->where('role_id', $roleId);
            }

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->paginateResponse($query, $request, "Get list users", function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => [
                    'id' => $user->role->id,
                    'name' => $user->role->name
                ],
                'avatar' => $user->avatar,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at
            ];
        });
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        try {
            // Kiểm tra quyền thêm người dùng
            // PermissionHelper::checkPermission('users', 'add');

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
                'avatar' => 'nullable|string|max:100',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id'],
                'avatar' => $validatedData['avatar'] ?? null,
                'email_verified_at' => now(),
            ]);

            return $this->successResponse(
                ['user' => $user->load('role')],
                'User created successfully',
                201
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified user
     */
    public function show(string $id)
    {
        try {
            // Kiểm tra quyền xem chi tiết người dùng
            // PermissionHelper::checkPermission('users', 'view');

            $user = User::with('role')->findOrFail($id);

            return $this->successResponse([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => [
                        'id' => $user->role->id,
                        'name' => $user->role->name
                    ],
                    'avatar' => $user->avatar,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at
                ]
            ]);

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('User not found', 404);
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, string $id)
    {
        try {
            // Kiểm tra quyền sửa người dùng
            // PermissionHelper::checkPermission('users', 'edit');

            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($id),
                ],
                'password' => 'nullable|string|min:8',
                'avatar' => 'nullable|string|max:100',
            ]);

            $updateData = [
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'avatar' => $validatedData['avatar'] ?? $user->avatar,
            ];

            // Only update password if provided
            if (!empty($validatedData['password'])) {
                $updateData['password'] = Hash::make($validatedData['password']);
            }

            $user->update($updateData);

            return $this->successResponse(
                ['user' => $user->fresh()->load('role')],
                'User updated successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(string $id)
    {
        try {
            // Kiểm tra quyền xóa người dùng
            // PermissionHelper::checkPermission('users', 'delete');

            $user = User::findOrFail($id);

            // Prevent deleting your own account
            if ($user->id === auth()->id()) {
                return $this->errorResponse('You cannot delete your own account', 422);
            }

            $user->delete();

            return $this->successResponse(null, 'User deleted successfully');

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('User not found', 404);
        }
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, string $id)
    {
        try {
            // Kiểm tra quyền thay đổi vai trò người dùng
            // PermissionHelper::checkPermission('users', 'change_role');

            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'role_id' => 'required|exists:roles,id',
            ]);

            $user->update([
                'role_id' => $validatedData['role_id'],
            ]);

            return $this->successResponse(
                ['user' => $user->fresh()->load('role')],
                'User role updated successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update user role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update user status (email verification)
     */
    public function updateStatus(Request $request, string $id)
    {
        try {
            // Kiểm tra quyền thay đổi trạng thái người dùng
            // PermissionHelper::checkPermission('users', 'change_status');

            $user = User::findOrFail($id);

            $validatedData = $request->validate([
                'verified' => 'required|boolean',
            ]);

            $user->update([
                'email_verified_at' => $validatedData['verified'] ? now() : null,
            ]);

            return $this->successResponse(
                ['user' => $user->fresh()],
                'User status updated successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update user status: ' . $e->getMessage(), 500);
        }
    }
}
