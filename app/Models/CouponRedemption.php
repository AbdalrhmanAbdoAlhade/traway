<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'coupon_id',
        'booking_id',
        'user_id',
        'discount_cents',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_cents' => 'integer',
            'released_at' => 'datetime',
        ];
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isReleased(): bool
    {
        return $this->released_at !== null;
    }
}
