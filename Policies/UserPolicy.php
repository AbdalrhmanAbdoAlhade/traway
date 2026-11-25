<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;
use App\Models\VisaApplication;

class UserPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'customers.view')
            || $this->platform($user, 'platform.customers.view');
    }

    public function view(User $user, User $model): bool
    {
        if ((int) $user->getKey() === (int) $model->getKey()) {
            return true;
        }

        if ($this->platform($user, 'platform.customers.view')) {
            return true;
        }

        $membership = $this->membership($user);

        if ($membership === null || ! $this->allows($user, 'customers.view')) {
            return false;
        }

        // A partner only sees a customer who actually transacted with it.
        return Booking::query()
            ->where('user_id', $model->getKey())
            ->where('organization_id', $membership->organization_id)
            ->exists()
            || VisaApplication::query()
                ->where('user_id', $model->getKey())
                ->where('organization_id', $membership->organization_id)
                ->exists();
    }

    public function update(User $user, User $model): bool
    {
        return $this->platform($user, 'platform.customers.update');
    }

    public function block(User $user, User $model): bool
    {
        return $this->platform($user, 'platform.customers.block');
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }

    public function restore(User $user, User $model): bool
    {
        return false;
    }

    public function forceDelete(User $user, User $model): bool
    {
        return false;
    }
}
