<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RoomType
 */
class RoomTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hotel_id' => $this->hotel_id,
            'name' => $this->name,
            'description' => $this->description,
            'max_adults' => $this->max_adults,
            'max_children' => $this->max_children,
            'max_occupancy' => $this->max_occupancy,
            'total_units' => $this->total_units,
            'base_price_cents' => $this->base_price_cents,
            'amenities' => $this->amenities ?? [],
            'cancellation_policy' => $this->cancellation_policy,
            'free_cancellation_hours_before' => $this->free_cancellation_hours_before,
            'allows_pay_at_hotel' => (bool) $this->allows_pay_at_hotel,
            'pay_now_discount_bp' => $this->pay_now_discount_bp,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
