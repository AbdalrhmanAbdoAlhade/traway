<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'coupons.view')
            || $this->platform($user, 'platform.coupons.view');
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->allows($user, 'coupons.view', ['resource' => $coupon])
            || $this->platform($user, 'platform.coupons.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'coupons.create')
            || $this->platform($user, 'platform.coupons.manage');
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->allows($user, 'coupons.manage', ['resource' => $coupon])
            || $this->platform($user, 'platform.coupons.manage');
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return $this->update($user, $coupon);
    }
}
