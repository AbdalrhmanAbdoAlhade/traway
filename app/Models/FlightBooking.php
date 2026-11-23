<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlightBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'trip_type',
        'cabin',
        'origin_iata',
        'destination_iata',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(FlightSegment::class)->orderBy('leg')->orderBy('sequence');
    }

    public function passengers(): HasMany
    {
        return $this->hasMany(FlightPassenger::class);
    }
}
