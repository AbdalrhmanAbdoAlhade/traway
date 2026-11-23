<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'tour_id',
        'tour_slot_id',
        'pickup_point_id',
        'participants',
        'child_participants',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TourSlot::class, 'tour_slot_id');
    }

    public function pickupPoint(): BelongsTo
    {
        return $this->belongsTo(TourPickupPoint::class, 'pickup_point_id');
    }
}
