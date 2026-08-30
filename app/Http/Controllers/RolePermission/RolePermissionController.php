<?php

namespace App\Http\Controllers\RolePermission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Repository\RolePermission\RolePermissionRepositoryInterface;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    private RolePermissionRepositoryInterface $modelRepository;

    public function __construct(RolePermissionRepositoryInterface $modelRepository)
    {
        $this->modelRepository = $modelRepository;
    }

    public function update(Request $request, string $roleId)
    {
        $validated = $request->validate([
            'permissions' => 'present|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role = Role::query()->findOrFail($roleId);

        if ($role->name === 'Admin') {
            $validated['permissions'] = Permission::query()->pluck('id')->all();
        }

        return $this->modelRepository->update($roleId, $validated);
    }
}
