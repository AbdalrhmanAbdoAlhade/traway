<?php

namespace App\Policies;

use App\Models\EsimPackage;
use App\Models\User;

class EsimPackagePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->platform($user, 'platform.esim_catalog.view');
    }

    public function view(User $user, EsimPackage $package): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->platform($user, 'platform.esim_catalog.manage');
    }

    public function update(User $user, EsimPackage $package): bool
    {
        return $this->platform($user, 'platform.esim_catalog.manage');
    }

    public function delete(User $user, EsimPackage $package): bool
    {
        return $this->platform($user, 'platform.esim_catalog.manage');
    }

    public function sync(User $user): bool
    {
        return $this->platform($user, 'platform.esim_catalog.sync');
    }
}
