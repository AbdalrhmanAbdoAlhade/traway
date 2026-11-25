<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'settlements.view')
            || $this->platform($user, 'platform.finance.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        $booking = $payment->booking;

        $tenantAllowed = $booking !== null
            && $this->allows($user, 'bookings.view', ['resource' => $booking]);

        return $tenantAllowed || $this->platform($user, 'platform.finance.view');
    }

    public function export(User $user): bool
    {
        return $this->platform($user, 'platform.finance.export');
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
