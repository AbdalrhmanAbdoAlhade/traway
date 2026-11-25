<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Settlement extends Model
{
    use HasFactory;

    public const STATUS_PENDING    = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_PAID       = 'paid';
    public const STATUS_FAILED     = 'failed';
    public const STATUS_CANCELLED  = 'cancelled';

    protected $fillable = [
        'organization_id',
        'period_start',
        'period_end',
        'status',
        'gross_amount_cents',
        'commission_amount_cents',
        'net_amount_cents',
        'currency',
        'entries_count',
        'notes',
        'processed_at',
        'paid_at',
    ];

    protected $casts = [
        'period_start'            => 'date',
        'period_end'              => 'date',
        'gross_amount_cents'      => 'integer',
        'commission_amount_cents' => 'integer',
        'net_amount_cents'        => 'integer',
        'entries_count'           => 'integer',
        'processed_at'            => 'datetime',
        'paid_at'                 => 'datetime',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SettlementItem::class);
    }
}