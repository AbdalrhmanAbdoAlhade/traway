<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EsimOrder extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_ACTIVATED = 'activated';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'booking_id',
        'esim_package_id',
        'provider_order_id',
        'activation_payload',
        'status',
        'activated_at',
    ];

    protected $hidden = [
        'activation_payload',
    ];

    protected function casts(): array
    {
        return [
            'activation_payload' => 'encrypted',
            'activated_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(EsimPackage::class, 'esim_package_id');
    }

    /**
     * An activated plan is no longer refundable (BR-059).
     */
    public function isActivated(): bool
    {
        return $this->status === self::STATUS_ACTIVATED;
    }
}
