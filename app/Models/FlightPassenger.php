<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightPassenger extends Model
{
    protected $fillable = [
        'flight_booking_id',
        'traveler_id',
        'type',
        'first_name',
        'last_name',
        'date_of_birth',
        'nationality',
        'passport_number',
        'passport_expires_at',
        'ticket_number',
    ];

    protected $hidden = [
        'passport_number',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_expires_at' => 'date',
            'passport_number' => 'encrypted',
        ];
    }

    public function flightBooking(): BelongsTo
    {
        return $this->belongsTo(FlightBooking::class);
    }

    public function traveler(): BelongsTo
    {
        return $this->belongsTo(Traveler::class);
    }
}
