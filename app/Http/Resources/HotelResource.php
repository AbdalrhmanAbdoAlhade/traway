<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Hotel
 */
class HotelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'name' => $this->name,
            'description' => $this->description,
            'amenities' => $this->amenities ?? [],
            'cancellation_policy' => $this->cancellation_policy,
            'country_code' => $this->country_code,
            'city' => $this->city,
            'address' => $this->address,
            'reception_phone' => $this->reception_phone,
            'contact_email' => $this->contact_email,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'star_rating' => $this->star_rating,
            'check_in_time' => $this->check_in_time ? substr($this->check_in_time, 0, 5) : null,
            'check_out_time' => $this->check_out_time ? substr($this->check_out_time, 0, 5) : null,
            'cover_image' => $this->cover_image,
            'gallery' => $this->gallery ?? [],
            'status' => $this->status,
            'published_at' => $this->published_at?->toISOString(),
            'rating_avg' => (float) $this->rating_avg,
            'rating_count' => (int) $this->rating_count,
            'room_types_count' => $this->whenCounted('roomTypes'),
        ];
    }
}
