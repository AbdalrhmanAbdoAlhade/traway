<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->platform($user, 'platform.partners.view');
    }

    public function view(User $user, Organization $organization): bool
    {
        return ($this->isMemberOf($user, $organization) && $this->allows($user, 'org.profile.view'))
            || $this->platform($user, 'platform.partners.view');
    }

    public function update(User $user, Organization $organization): bool
    {
        return ($this->isMemberOf($user, $organization) && $this->allows($user, 'org.profile.update'))
            || $this->platform($user, 'platform.partners.update');
    }

    public function approve(User $user, Organization $organization): bool
    {
        return $this->platform($user, 'platform.partners.create');
    }

    public function suspend(User $user, Organization $organization): bool
    {
        return ! $organization->isPlatform()
            && $this->platform($user, 'platform.partners.suspend');
    }

    public function reinstate(User $user, Organization $organization): bool
    {
        return $this->suspend($user, $organization);
    }

    public function close(User $user, Organization $organization): bool
    {
        return ! $organization->isPlatform()
            && $this->isOwnerOf($user, $organization);
    }

    public function transferOwnership(User $user, Organization $organization): bool
    {
        return $this->isOwnerOf($user, $organization);
    }

    public function manageAccess(User $user, Organization $organization): bool
    {
        return $this->platform($user, 'platform.organization_access.manage');
    }

    public function delete(User $user, Organization $organization): bool
    {
        return false;
    }

    public function restore(User $user, Organization $organization): bool
    {
        return false;
    }

    public function forceDelete(User $user, Organization $organization): bool
    {
        return false;
    }
}
