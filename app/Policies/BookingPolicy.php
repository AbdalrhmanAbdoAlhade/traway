<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'bookings.view')
            || $this->platform($user, 'platform.bookings.view');
    }

    public function view(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.view', ['resource' => $booking])
            || $this->platform($user, 'platform.bookings.view');
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'bookings.create');
    }

    public function update(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.update', ['resource' => $booking]);
    }

    public function cancel(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.cancel', ['resource' => $booking]);
    }

    public function refund(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.refund', ['resource' => $booking]);
    }

    public function checkIn(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.checkin', ['resource' => $booking]);
    }

    public function checkOut(User $user, Booking $booking): bool
    {
        return $this->allows($user, 'bookings.checkout', ['resource' => $booking]);
    }

    public function export(User $user): bool
    {
        return $this->allows($user, 'bookings.export')
            || $this->platform($user, 'platform.bookings.export');
    }

    public function delete(User $user, Booking $booking): bool
    {
        return false;
    }
}
