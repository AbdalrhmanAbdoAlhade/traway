<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    /**
     * Determine whether the user can view any permissions.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('permissions.view')
            || $user->can('permissions.manage');
    }

    /**
     * Determine whether the user can view a permission.
     */
    public function view(User $user, Permission $permission): bool
    {
        return $user->can('permissions.view')
            || $user->can('permissions.manage');
    }

    /**
     * Determine whether the user can create permissions.
     */
    public function create(User $user): bool
    {
        return $user->can('permissions.manage');
    }

    /**
     * Determine whether the user can update permissions.
     */
    public function update(User $user, Permission $permission): bool
    {
        return $user->can('permissions.manage');
    }

    /**
     * Determine whether the user can delete permissions.
     */
    public function delete(User $user, Permission $permission): bool
    {
        return $user->can('permissions.manage');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return false;
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
