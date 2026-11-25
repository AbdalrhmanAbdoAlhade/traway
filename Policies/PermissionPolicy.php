<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

/**
 * The permission catalogue is seeded from code (RolesAndPermissionsSeeder), so
 * there is deliberately no create/update/delete ability. Viewing it requires the
 * same access as viewing roles.
 */
class PermissionPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'roles.view')
            || $this->platform($user, 'platform.roles.view');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Permission $permission): bool
    {
        return false;
    }

    public function delete(User $user, Permission $permission): bool
    {
        return false;
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
