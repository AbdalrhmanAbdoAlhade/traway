<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TourSlot extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    // booked_count is changed only through atomic conditional updates in the booking service.
    protected $fillable = [
        'tour_id',
        'starts_at',
        'capacity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'capacity' => 'integer',
            'booked_count' => 'integer',
        ];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function remainingSeats(): int
    {
        return max(0, $this->capacity - $this->booked_count);
    }

    /**
     * BR-075: future, open, with remaining seats.
     */
    public function scopeBookable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN)
            ->where('starts_at', '>', now())
            ->whereColumn('booked_count', '<', 'capacity');
    }
}
