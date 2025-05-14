<?php

namespace App\Http\Controllers\Dashboard\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\GeneralResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    public function updateRole(Request $request, User $user): JsonResource
    {
        $request->validate(['role' => ['required', 'exists:roles,name']]);
        $user->syncRoles($request->role);

        return new GeneralResource($user);
    }

    public function updateUserPermission(Request $request, User $user): JsonResource
    {
        $user->syncPermissions($request->all());
        return new GeneralResource($user);
    }

    public function updateRolePermission(Request $request, Role $role): JsonResource
    {
        $role->syncPermissions($request->permissions);
        return new GeneralResource($role);
    }

    public function rolePermissions(Role $role): JsonResource
    {
        return new GeneralResource($role->getAllPermissions());
    }

    public function permissions(): JsonResource
    {
        return GeneralResource::collection(Permission::all());
    }

    public function roles(): JsonResource
    {
        return GeneralResource::collection(Role::all());
    }
}
