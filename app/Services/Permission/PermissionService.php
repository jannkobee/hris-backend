<?php

namespace App\Services\Permission;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class PermissionService implements PermissionServiceInterface
{
    public function hasPermission(string $action, $user): bool
    {
        return $user instanceof User && $user->hasPermission($action);
    }

    public function getPermissionsForUser($user): array
    {
        if (! $user instanceof User) {
            return [];
        }

        if ($user->role?->name === 'Admin') {
            return Permission::query()->orderBy('slug')->pluck('slug')->all();
        }

        $user->role?->loadMissing('permissions');

        return $user->role?->permissions->pluck('slug')->sort()->values()->all() ?? [];
    }

    public function assignPermission(string $permission, $user): void
    {
        $role = $this->roleFor($user);
        $permissionId = Permission::query()->where('slug', $permission)->value('id');

        if (! $permissionId) {
            throw (new ModelNotFoundException())->setModel(Permission::class, [$permission]);
        }

        $role->permissions()->syncWithoutDetaching([$permissionId]);
        $role->unsetRelation('permissions');
    }

    public function revokePermission(string $permission, $user): void
    {
        $role = $this->roleFor($user);
        $permissionId = Permission::query()->where('slug', $permission)->value('id');

        if ($permissionId) {
            $role->permissions()->detach($permissionId);
            $role->unsetRelation('permissions');
        }
    }

    private function roleFor(mixed $user): Role
    {
        if (! $user instanceof User || ! $user->role) {
            throw new InvalidArgumentException('A user with an assigned role is required.');
        }

        return $user->role;
    }
}
