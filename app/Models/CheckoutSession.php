<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CheckoutSession extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PRICE_CHANGED = 'price_changed';
    public const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'service',
        'status',
        'idempotency_key',
        'quote',
        'quoted_total_cents',
        'revalidated_total_cents',
        'price_changed_at',
        'price_change_accepted_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quote' => 'array',
            'quoted_total_cents' => 'integer',
            'revalidated_total_cents' => 'integer',
            'price_changed_at' => 'datetime',
            'price_change_accepted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class);
    }

    public function priceChanged(): bool
    {
        return $this->revalidated_total_cents !== null
            && $this->revalidated_total_cents !== $this->quoted_total_cents;
    }

    /**
     * Payment is allowed only when the price did not change, or the customer accepted the new one.
     */
    public function canProceedToPayment(): bool
    {
        return ! $this->priceChanged() || $this->price_change_accepted_at !== null;
    }
}
