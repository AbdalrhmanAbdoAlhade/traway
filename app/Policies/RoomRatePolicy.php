<?php

namespace App\Policies;

use App\Models\RoomRate;
use App\Models\User;

class RoomRatePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'rates.view');
    }

    public function view(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.view', $this->context($rate));
    }

    public function update(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.update', $this->context($rate));
    }

    public function stopSell(User $user, RoomRate $rate): bool
    {
        return $this->allows($user, 'rates.stop_sell', $this->context($rate));
    }

    /**
     * @return array{resource: ?\Illuminate\Database\Eloquent\Model, hotel_id: ?int}
     */
    private function context(RoomRate $rate): array
    {
        $hotel = $rate->roomType?->hotel;

        return [
            'resource' => $hotel,
            'hotel_id' => $hotel?->getKey(),
        ];
    }
}
