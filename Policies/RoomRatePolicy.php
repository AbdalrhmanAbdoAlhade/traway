<?php

namespace App\Policies;

use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\User;

class RoomRatePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'rates.view', ['listing' => true]);
    }

    public function view(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.view', $this->context($rate->roomType));
    }

    public function update(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.update', $this->context($rate->roomType));
    }

    public function stopSell(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.stop_sell', $this->context($rate->roomType));
    }

    public function viewCalendar(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rates.view', $this->context($roomType));
    }

    public function updateCalendar(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rates.update', $this->context($roomType));
    }

    public function stopSellCalendar(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rates.stop_sell', $this->context($roomType));
    }

    /**
     * @return array{resource: ?\Illuminate\Database\Eloquent\Model, hotel_id: ?int}
     */
    private function context(?RoomType $roomType): array
    {
        $hotel = $roomType?->hotel;

        return [
            'resource' => $hotel,
            'hotel_id' => $hotel?->getKey(),
        ];
    }
}
