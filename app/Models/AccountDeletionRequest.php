<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountDeletionRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_BLOCKED = 'blocked';
    public const STATUS_ANONYMIZED = 'anonymized';

    public const BLOCKED_UPCOMING_TRIP = 'upcoming_trip';
    public const BLOCKED_VISA_UNDER_REVIEW = 'visa_under_review';
    public const BLOCKED_REFUND_PENDING = 'refund_pending';

    protected $fillable = [
        'user_id',
        'status',
        'requested_at',
        'grace_ends_at',
        'cancelled_at',
        'completed_at',
        'blocked_reason',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'grace_ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // DB unique index on pending_user_key => one open request per user.
        static::saving(function (AccountDeletionRequest $request) {
            $request->pending_user_key = $request->status === self::STATUS_PENDING
                ? $request->user_id
                : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Requests whose 7-day grace period is over and which are ready for anonymization.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->pending()->where('grace_ends_at', '<=', now());
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
