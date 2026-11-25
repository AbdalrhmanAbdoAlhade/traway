<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;

class MembershipPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'employees.view');
    }

    public function view(User $user, Membership $membership): bool
    {
        if ((int) $membership->user_id === (int) $user->getKey()) {
            return true;
        }

        return $this->allows($user, 'employees.view', ['resource' => $membership]);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'employees.create')
            || $this->platform($user, 'platform.organization_access.manage');
    }

    public function update(User $user, Membership $membership): bool
    {
        if (! $this->allows($user, 'employees.update', ['resource' => $membership])) {
            return false;
        }

        // An owner membership is never edited by a non-owner employee.
        return ! $membership->is_owner || $this->actorOwnsOrganization($user, $membership);
    }

    public function assignRole(User $user, Membership $membership): bool
    {
        return $this->allows($user, 'roles.assign', ['resource' => $membership]);
    }

    public function grantPermission(User $user, Membership $membership): bool
    {
        return $this->allows($user, 'employees.update', ['resource' => $membership]);
    }

    public function deactivate(User $user, Membership $membership): bool
    {
        if ((int) $membership->user_id === (int) $user->getKey()) {
            return false;
        }

        if ($membership->is_owner) {
            return false;
        }

        return $this->allows($user, 'employees.deactivate', ['resource' => $membership]);
    }

    public function reinstate(User $user, Membership $membership): bool
    {
        if ($membership->is_owner) {
            return false;
        }

        return $this->allows($user, 'employees.deactivate', ['resource' => $membership]);
    }

    public function transfer(User $user, Membership $membership): bool
    {
        if ($membership->is_owner) {
            return false;
        }

        if ($this->platform($user, 'platform.organization_access.manage')) {
            return true;
        }

        $organization = $membership->organization;

        return $organization !== null && $this->isOwnerOf($user, $organization);
    }

    public function delete(User $user, Membership $membership): bool
    {
        return false;
    }

    private function actorOwnsOrganization(User $user, Membership $membership): bool
    {
        $organization = Organization::query()->find($membership->organization_id);

        return $organization !== null && $this->isOwnerOf($user, $organization);
    }
}
