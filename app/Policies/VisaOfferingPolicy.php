<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisaOffering;

class VisaOfferingPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'visa_offerings.view')
            || $this->platform($user, 'platform.visa_catalog.view');
    }

    public function view(User $user, VisaOffering $offering): bool
    {
        return $this->allows($user, 'visa_offerings.view', ['resource' => $offering])
            || $this->platform($user, 'platform.visa_catalog.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'visa_offerings.manage')
            || $this->platform($user, 'platform.visa_catalog.manage');
    }

    public function update(User $user, VisaOffering $offering): bool
    {
        return $this->allows($user, 'visa_offerings.manage', ['resource' => $offering])
            || $this->platform($user, 'platform.visa_catalog.manage');
    }

    public function delete(User $user, VisaOffering $offering): bool
    {
        return $this->update($user, $offering);
    }
}
