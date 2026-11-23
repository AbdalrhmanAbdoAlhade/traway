<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

class Payment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_AUTHORIZED = 'authorized';
    public const STATUS_CAPTURED = 'captured';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VOIDED = 'voided';

    protected $fillable = [
        'reference',
        'booking_id',
        'user_id',
        'payment_method_id',
        'provider',
        'provider_payment_id',
        'idempotency_key',
        'amount_cents',
        'currency',
        'status',
        'failure_code',
        'card_brand',
        'card_last4',
        'captured_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'captured_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // BR-102: the transaction log is append-only. Only status-related fields may change.
        static::updating(function (Payment $payment) {
            if ($payment->isDirty(['reference', 'booking_id', 'user_id', 'amount_cents', 'currency', 'idempotency_key'])) {
                throw new LogicException('Payment amount, reference and owner cannot be edited.');
            }
        });

        static::deleting(function () {
            throw new LogicException('Payments cannot be deleted.');
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isCaptured(): bool
    {
        return $this->status === self::STATUS_CAPTURED;
    }
}
