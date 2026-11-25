<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Booking
 */
class HotelBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stay = $this->relationLoaded('hotel') ? $this->hotel : null;
        $showContact = (bool) $request->attributes->get('show_contact', false);

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'hotel' => [
                'id' => $this->hotel_id,
                'name' => $this->whenLoaded('hotelProperty', fn () => $this->hotelProperty?->name),
            ],
            'room_type' => $stay?->roomType ? [
                'id' => $stay->roomType->id,
                'name' => $stay->roomType->name,
            ] : null,
            'stay' => $stay ? [
                'check_in' => $stay->check_in?->toDateString(),
                'check_out' => $stay->check_out?->toDateString(),
                'nights' => $stay->nights,
                'rooms' => $stay->rooms,
                'adults' => $stay->adults,
                'children' => $stay->children,
                'payment_timing' => $stay->payment_timing,
                'guest_name' => $stay->guest_name,
                'room_number' => $stay->room_number,
                'checked_in_at' => $stay->checked_in_at?->toISOString(),
                'checked_out_at' => $stay->checked_out_at?->toISOString(),
            ] : null,
            'total_cents' => $this->total_cents,
            'currency' => $this->currency,
            'contact' => $showContact ? [
                'name' => $this->contact_name,
                'email' => $this->contact_email,
                'phone' => $this->contact_phone,
            ] : null,
            'confirmed_at' => $this->confirmed_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'history' => $this->whenLoaded('statusHistories', fn () => $this->statusHistories->map(fn ($h) => [
                'from' => $h->from_status,
                'to' => $h->to_status,
                'note' => $h->note,
                'at' => $h->created_at?->toISOString(),
            ])->values()),
        ];
    }
}
