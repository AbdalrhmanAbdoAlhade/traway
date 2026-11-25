<?php

namespace App\Events\Membership;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OwnershipTransferred
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public User $previousOwner,
        public User $newOwner,
    ) {}
}
