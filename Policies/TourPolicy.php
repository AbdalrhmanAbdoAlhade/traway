<?php

namespace App\Policies;

use App\Models\Tour;
use App\Models\User;

class TourPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'tours.view');
    }

    public function view(User $user, Tour $tour): bool
    {
        return $this->allows($user, 'tours.view', ['resource' => $tour]);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'tours.create');
    }

    public function update(User $user, Tour $tour): bool
    {
        return $this->allows($user, 'tours.update', ['resource' => $tour]);
    }

    public function delete(User $user, Tour $tour): bool
    {
        return $this->allows($user, 'tours.delete', ['resource' => $tour]);
    }

    public function submit(User $user, Tour $tour): bool
    {
        return $this->allows($user, 'tours.submit', ['resource' => $tour]);
    }

    public function manageSlots(User $user, Tour $tour): bool
    {
        return $this->allows($user, 'tours.slots.manage', ['resource' => $tour]);
    }
}
