<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PartnerLedgerEntry extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_SALE = 'sale';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_REFUND = 'refund';
    public const TYPE_GATEWAY_FEE = 'gateway_fee';
    public const TYPE_CHARGEBACK = 'chargeback';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_ADJUSTMENT = 'adjustment';

    protected $fillable = [
        'organization_id',
        'entry_key',
        'type',
        'amount_cents',
        'booking_id',
        'refund_id',
        'payout_request_id',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Append-only ledger: corrections are made with a new adjustment entry.
        static::updating(function () {
            throw new LogicException('Ledger entries are append-only.');
        });

        static::deleting(function () {
            throw new LogicException('Ledger entries are append-only.');
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function refund(): BelongsTo
    {
        return $this->belongsTo(Refund::class);
    }

      public function settlement(): BelongsTo
  {
      return $this->belongsTo(Settlement::class);
  }
  	
    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class);
    }
}
