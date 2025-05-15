<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PermissionHelper;
use App\Http\Controllers\BaseController;
use App\Models\Role;
use App\Models\Permission;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends BaseController
{
    /**
     * Display a listing of roles
     */
    public function index(Request $request)
    {
        try {
            // Kiểm tra quyền xem danh sách vai trò
            // PermissionHelper::checkPermission('roles', 'view');

            $query = Role::query();

            // Filter by name if provided
            if ($request->has('name')) {
                $name = $request->input('name');
                $query->where('name', 'LIKE', "%{$name}%");
            }

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        }

        return $this->paginateResponse($query, $request, "Get list roles", function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at
            ];
        });
    }

    /**
     * Store a newly created role
     */
    public function store(Request $request)
    {
        try {
            // Kiểm tra quyền thêm vai trò
//            PermissionHelper::checkPermission('roles', 'add');

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:roles,name',
            ]);

            $role = Role::create([
                'name' => $validatedData['name'],
            ]);

            return $this->successResponse(
                ['role' => $role],
                'Role created successfully',
                201
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified role
     */
    public function show(string $id)
    {
        try {
            // Kiểm tra quyền xem chi tiết vai trò
            // PermissionHelper::checkPermission('roles', 'view');

            $role = Role::findOrFail($id);

            // Get permissions for this role
            $permissions = DB::table('permissions')
                ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->where('role_permissions.role_id', $id)
                ->select('permissions.id', 'permissions.function', 'permissions.action', 'permissions.key')
                ->get();

            return $this->successResponse([
                'id' => $role->id,
                'name' => $role->name,
                'created_at' => $role->created_at,
                'updated_at' => $role->updated_at,
                'permissions' => $permissions
            ]);

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Role not found', 404);
        }
    }

    /**
     * Update the specified role
     */
    public function update(Request $request, string $id)
    {
        try {
            // Kiểm tra quyền sửa vai trò
//            PermissionHelper::checkPermission('roles', 'edit');

            $role = Role::findOrFail($id);

            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('roles')->ignore($id),
                ],
            ]);

            $role->update([
                'name' => $validatedData['name'],
            ]);

            return $this->successResponse(
                ['role' => $role->fresh()],
                'Role updated successfully'
            );

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update role: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified role
     */
    public function destroy(string $id)
    {
        try {
            // Kiểm tra quyền xóa vai trò
//            PermissionHelper::checkPermission('roles', 'delete');

            $role = Role::findOrFail($id);

            // Check if role is being used by any users
            $usersCount = DB::table('users')->where('role_id', $id)->count();
            if ($usersCount > 0) {
                return $this->errorResponse("Cannot delete role because it's assigned to {$usersCount} users", 422);
            }

            // Delete role permissions first
            DB::table('role_permissions')->where('role_id', $id)->delete();

            // Delete role
            $role->delete();

            return $this->successResponse(null, 'Role deleted successfully');

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Role not found', 404);
        }
    }

    /**
     * Get all permissions
     */
    public function getAllPermissions()
    {
        try {
            // Kiểm tra quyền xem danh sách quyền
            // PermissionHelper::checkPermission('roles', 'view');

            $permissions = Permission::all();

            // Group permissions by function
            $permissionsName = [];
            $groupedPermissions = [];
            foreach ($permissions as $permission) {
                if (!isset($groupedPermissions[$permission->function])) {
                    $groupedPermissions[$permission->function] = [];
                }

                $permissionsName[$permission->function] = $permission->function_name;
                $groupedPermissions[$permission->function][] = [
                    'id' => $permission->id,
                    'action' => $permission->action,
                    'key' => $permission->key
                ];
            }

            return $this->successResponse([
                'permissions_name' => $permissionsName,
                'permissions' => $groupedPermissions
            ]);

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to get permissions: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Update role permissions
     */
    public function updatePermissions(Request $request, string $id)
    {
        try {
            // Kiểm tra quyền cập nhật quyền cho vai trò
//            PermissionHelper::checkPermission('roles', 'edit_permissions');

            $role = Role::findOrFail($id);

            $validatedData = $request->validate([
                'permissions' => 'required|array',
                'permissions.*' => 'exists:permissions,id'
            ]);

            // Begin transaction
            DB::beginTransaction();

            try {
                // Delete existing permissions
                DB::table('role_permissions')->where('role_id', $id)->delete();

                // Add new permissions
                $now = now();
                $permissionsToInsert = [];

                foreach ($validatedData['permissions'] as $permissionId) {
                    $permissionsToInsert[] = [
                        'role_id' => $id,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                }

                if (!empty($permissionsToInsert)) {
                    DB::table('role_permissions')->insert($permissionsToInsert);
                }

                DB::commit();

                return $this->successResponse(
                    ['role' => $role->fresh()],
                    'Role permissions updated successfully'
                );

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update role permissions: ' . $e->getMessage(), 500);
        }
    }
}
