<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class PayoutRequest extends Model
{
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_TRANSFERRED = 'transferred';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    public const METHOD_MANUAL = 'manual';
    public const METHOD_GATEWAY = 'gateway';

    protected $fillable = [
        'reference',
        'organization_id',
        'amount_cents',
        'status',
        'method',
        'provider_payout_id',
        'requested_by',
        'processed_by',
        'transferred_at',
        'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'transferred_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'subject');
    }
}
