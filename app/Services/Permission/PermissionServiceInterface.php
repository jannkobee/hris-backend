<?php

namespace App\Services\Permission;

interface PermissionServiceInterface
{
    /**
     * Check if the user has permission to perform the action.
     *
     * @param  mixed  $user
     */
    public function hasPermission(string $action, $user): bool;

    /**
     * Get all permissions for a user.
     *
     * @param  mixed  $user
     */
    public function getPermissionsForUser($user): array;

    /**
     * Assign a permission to a user.
     *
     * @param  mixed  $user
     */
    public function assignPermission(string $permission, $user): void;

    /**
     * Revoke a permission from a user.
     *
     * @param  mixed  $user
     */
    public function revokePermission(string $permission, $user): void;
}
