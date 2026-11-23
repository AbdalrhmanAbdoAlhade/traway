<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can view a role.
     */
    public function view(User $user, Role $role): bool
    {
        return $user->can('roles.view');
    }

    /**
     * Determine whether the user can create roles.
     */
    public function create(User $user): bool
    {
        return $user->can('roles.create');
    }

    /**
     * Determine whether the user can update a role.
     */
    public function update(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin') {
            return false;
        }

        return $user->can('roles.update');
    }

    /**
     * Determine whether the user can delete a role.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($role->name === 'Super Admin') {
            return false;
        }

        return $user->can('roles.delete');
    }

    /**
     * Determine whether the user can assign permissions to a role.
     */
    public function assignPermissions(
        User $user,
        Role $role,
        array $permissionNames
    ): bool {
        if ($role->name === 'Super Admin') {
            return false;
        }

        if (! $user->can('roles.update')) {
            return false;
        }

        foreach ($permissionNames as $permissionName) {
            if (
                ! is_string($permissionName) ||
                ! $user->hasPermissionTo($permissionName, 'web')
            ) {
                return false;
            }
        }

        return true;
    }

    public function restore(User $user, Role $role): bool
    {
        return false;
    }

    public function forceDelete(User $user, Role $role): bool
    {
        return false;
    }
}
