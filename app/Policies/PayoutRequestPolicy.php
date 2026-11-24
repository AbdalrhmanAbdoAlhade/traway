<?php

namespace App\Policies;

use App\Models\PayoutRequest;
use App\Models\User;

class PayoutRequestPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'settlements.view')
            || $this->platform($user, 'platform.finance.view');
    }

    public function view(User $user, PayoutRequest $payoutRequest): bool
    {
        return $this->allows($user, 'settlements.view', ['resource' => $payoutRequest])
            || $this->platform($user, 'platform.finance.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'settlements.manage');
    }

    public function process(User $user, PayoutRequest $payoutRequest): bool
    {
        return $this->platform($user, 'platform.finance.settlements.execute');
    }

    public function delete(User $user, PayoutRequest $payoutRequest): bool
    {
        return false;
    }
}
