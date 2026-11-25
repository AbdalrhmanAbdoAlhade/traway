<?php

namespace App\Policies;

use App\Models\Refund;
use App\Models\User;

class RefundPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'settlements.view')
            || $this->platform($user, 'platform.finance.view');
    }

    public function view(User $user, Refund $refund): bool
    {
        $booking = $refund->booking;

        $tenantAllowed = $booking !== null
            && $this->allows($user, 'bookings.view', ['resource' => $booking]);

        return $tenantAllowed || $this->platform($user, 'platform.finance.view');
    }

    public function create(User $user, Refund $refund): bool
    {
        $booking = $refund->booking;

        return $booking !== null
            && $this->allows($user, 'bookings.refund', ['resource' => $booking]);
    }

    public function process(User $user, Refund $refund): bool
    {
        return $this->platform($user, 'platform.finance.settlements.execute');
    }

    public function delete(User $user, Refund $refund): bool
    {
        return false;
    }
}
