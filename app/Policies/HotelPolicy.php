<?php

namespace App\Policies;

use App\Models\Hotel;
use App\Models\User;

class HotelPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'org.profile.view')
            || $this->platform($user, 'platform.partners.view');
    }

    public function view(User $user, Hotel $hotel): bool
    {
        return $this->allows($user, 'org.profile.view', ['resource' => $hotel])
            || $this->platform($user, 'platform.partners.view');
    }

    public function update(User $user, Hotel $hotel): bool
    {
        return $this->allows($user, 'org.profile.update', ['resource' => $hotel])
            || $this->platform($user, 'platform.partners.update');
    }

    public function publish(User $user, Hotel $hotel): bool
    {
        return $this->allows($user, 'org.profile.publish', ['resource' => $hotel]);
    }

    public function delete(User $user, Hotel $hotel): bool
    {
        return false;
    }

    public function restore(User $user, Hotel $hotel): bool
    {
        return false;
    }

    public function forceDelete(User $user, Hotel $hotel): bool
    {
        return false;
    }
}
