<?php

namespace App\Policies;

use App\Models\OrganizationApplication;
use App\Models\User;

class OrganizationApplicationPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->platform($user, 'platform.partner_requests.view');
    }

    public function view(User $user, OrganizationApplication $application): bool
    {
        return $this->viewAny($user);
    }

    public function review(User $user, OrganizationApplication $application): bool
    {
        return $this->platform($user, 'platform.partner_requests.review');
    }

    public function approve(User $user, OrganizationApplication $application): bool
    {
        return $this->review($user, $application);
    }

    public function reject(User $user, OrganizationApplication $application): bool
    {
        return $this->review($user, $application);
    }

    public function delete(User $user, OrganizationApplication $application): bool
    {
        return false;
    }
}
