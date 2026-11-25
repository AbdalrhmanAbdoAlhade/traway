<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotelBooking extends Model
{
    public const PAY_NOW = 'pay_now';
    public const PAY_AT_HOTEL = 'pay_at_hotel';

       protected $fillable = [
        'booking_id',
        'hotel_id',
        'room_type_id',
        'check_in',
        'check_out',
        'nights',
        'adults',
        'children',
        'rooms',
        'payment_timing',
        'guest_name',
        'room_number',
        'checked_in_at',
        'checked_out_at',
        'checked_in_by_membership_id',
        'checked_out_by_membership_id',
    ];

    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }
}
