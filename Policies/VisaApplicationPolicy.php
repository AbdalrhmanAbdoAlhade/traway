<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VisaApplication;

class VisaApplicationPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'visa_requests.view')
            || $this->platform($user, 'platform.visa_requests.view');
    }

    public function view(User $user, VisaApplication $application): bool
    {
        return $this->allows($user, 'visa_requests.view', ['resource' => $application])
            || $this->platform($user, 'platform.visa_requests.view');
    }

    /**
     * Company staff may validate documents and flag missing items.
     */
    public function review(User $user, VisaApplication $application): bool
    {
        return $this->allows($user, 'visa_requests.review', ['resource' => $application])
            || $this->platform($user, 'platform.visa_requests.review');
    }

    /**
     * Final approval or rejection belongs to Traway staff only (DEC-07).
     */
    public function decide(User $user, VisaApplication $application): bool
    {
        return $this->platform($user, 'platform.visa_requests.decide');
    }

    public function assign(User $user, VisaApplication $application): bool
    {
        return $this->platform($user, 'platform.visa_requests.assign');
    }

    /**
     * There is no partner-side status transition: review + platform decision only.
     */
    public function updateStatus(User $user, VisaApplication $application): bool
    {
        return false;
    }
}
