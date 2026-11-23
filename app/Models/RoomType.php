<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoomType extends Model
{
    use HasTranslations;
    use SoftDeletes;

    public const CANCELLATION_FREE_UNTIL_DEADLINE = 'free_until_deadline';
    public const CANCELLATION_NON_REFUNDABLE = 'non_refundable';

    protected $fillable = [
        'hotel_id',
        'name',
        'description',
        'max_adults',
        'max_children',
        'max_occupancy',
        'total_units',
        'base_price_cents',
        'amenities',
        'cancellation_policy',
        'free_cancellation_hours_before',
        'allows_pay_at_hotel',
        'pay_now_discount_bp',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'amenities' => 'array',
            'base_price_cents' => 'integer',
            'allows_pay_at_hotel' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function hotel(): BelongsTo
    {
        return $this->belongsTo(Hotel::class);
    }

    public function rates(): HasMany
    {
        return $this->hasMany(RoomRate::class);
    }
}
