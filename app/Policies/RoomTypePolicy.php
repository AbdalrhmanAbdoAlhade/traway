<?php

namespace App\Policies;

use App\Models\RoomType;
use App\Models\User;

class RoomTypePolicy extends TenantPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'rooms.view');
    }

    public function view(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rooms.view', $this->context($roomType));
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'rooms.manage');
    }

    public function update(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rooms.manage', $this->context($roomType));
    }

    public function delete(User $user, RoomType $roomType): bool
    {
        return $this->allows($user, 'rooms.manage', $this->context($roomType));
    }

    /**
     * @return array{resource: ?\Illuminate\Database\Eloquent\Model, hotel_id: ?int}
     */
    private function context(RoomType $roomType): array
    {
        return [
            'resource' => $roomType->hotel,
            'hotel_id' => $roomType->hotel_id,
        ];
    }
}
