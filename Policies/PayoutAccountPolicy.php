<?php

namespace App\Policies;

use App\Models\PayoutAccount;
use App\Models\User;

class PayoutAccountPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'settlements.view');
    }

    public function view(User $user, PayoutAccount $account): bool
    {
        return $this->allows($user, 'settlements.view', ['resource' => $account])
            || $this->platform($user, 'platform.finance.view');
    }

    public function create(User $user): bool
    {
        // settlements.manage is non-delegable: owner only.
        return $this->allows($user, 'settlements.manage');
    }

    public function update(User $user, PayoutAccount $account): bool
    {
        return $this->allows($user, 'settlements.manage', ['resource' => $account]);
    }

    public function delete(User $user, PayoutAccount $account): bool
    {
        return $this->update($user, $account);
    }

    public function verify(User $user, PayoutAccount $account): bool
    {
        return $this->platform($user, 'platform.finance.settlements.execute');
    }
}
