<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

/**
 * Roles are team-scoped. Global templates and system/owner roles are immutable;
 * platform roles are managed only inside the platform organization; nobody can
 * grant a permission they do not hold themselves.
 */
class RolePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'roles.view')
            || $this->platform($user, 'platform.roles.view');
    }

    public function view(User $user, Role $role): bool
    {
        if ($role->is_template) {
            return $this->allows($user, 'roles.view')
                || $this->platform($user, 'platform.roles.view');
        }

        if ($role->family === Role::FAMILY_PLATFORM) {
            return $this->platform($user, 'platform.roles.view')
                && $this->roleBelongsToPlatform($role);
        }

        $membership = $this->membership($user);

        return $membership !== null
            && (int) $role->organization_id === (int) $membership->organization_id
            && $this->allows($user, 'roles.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'roles.manage')
            || $this->platform($user, 'platform.roles.manage');
    }

    public function update(User $user, Role $role): bool
    {
        if (! $this->isMutable($role)) {
            return false;
        }

        if ($role->family === Role::FAMILY_PLATFORM) {
            return $this->platform($user, 'platform.roles.manage')
                && $this->roleBelongsToPlatform($role);
        }

        $membership = $this->membership($user);

        return $membership !== null
            && (int) $role->organization_id === (int) $membership->organization_id
            && $this->allows($user, 'roles.manage');
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }

    /**
     * A role may only be given permissions the acting user holds and may delegate.
     *
     * @param  array<int, string>  $permissionNames
     */
    public function assignPermissions(User $user, Role $role, array $permissionNames = []): bool
    {
        if (! $this->update($user, $role)) {
            return false;
        }

        if ($role->family === Role::FAMILY_PLATFORM) {
            return $this->platformHoldsAll($user, $permissionNames);
        }

        $membership = $this->membership($user);

        if ($membership === null) {
            return false;
        }

        $held = $this->authz->effectivePermissions($membership);

        foreach ($permissionNames as $name) {
            if (! is_string($name)) {
                return false;
            }

            $permission = Permission::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->first();

            if ($permission === null
                || ! $permission->isDelegable()
                || ! $permission->allowedForOrganizationType($membership->organization->type)
                || ! in_array($permission->name, $held, true)) {
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

    private function isMutable(Role $role): bool
    {
        return ! $role->is_template && ! $role->is_system;
    }

    private function roleBelongsToPlatform(Role $role): bool
    {
        $platform = $this->platformOrganization();

        return $platform !== null && (int) $role->organization_id === (int) $platform->getKey();
    }

    /**
     * @param  array<int, string>  $permissionNames
     */
    private function platformHoldsAll(User $user, array $permissionNames): bool
    {
        $platform = $this->platformOrganization();

        if ($platform === null) {
            return false;
        }

        $membership = $this->authz->activeMembership($user, $platform);

        if ($membership === null) {
            return false;
        }

        $held = $this->authz->effectivePermissions($membership);

        foreach ($permissionNames as $name) {
            if (! is_string($name) || ! in_array($name, $held, true)) {
                return false;
            }
        }

        return true;
    }
}
