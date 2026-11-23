<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightSegment extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'leg',
        'sequence',
        'airline_code',
        'flight_number',
        'origin_iata',
        'destination_iata',
        'departs_at',
        'arrives_at',
    ];

    protected function casts(): array
    {
        return [
            'departs_at' => 'datetime',
            'arrives_at' => 'datetime',
        ];
    }

    public function flightBooking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class);
    }
}
