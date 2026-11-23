<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tour extends Model
{
    use BelongsToOrganization;
    use HasTranslations;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_DOCUMENTS_REQUIRED = 'documents_required';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'organization_id',
        'title',
        'description',
        'country_code',
        'destination_city',
        'duration_minutes',
        'group_size_max',
        'price_cents',
        'child_price_cents',
        'cover_image',
        'gallery',
        'status',
        'rejection_reason',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'title' => 'array',
            'description' => 'array',
            'gallery' => 'array',
            'price_cents' => 'integer',
            'child_price_cents' => 'integer',
            'rating_avg' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function itineraryDays(): HasMany
    {
        return $this->hasMany(TourItineraryDay::class)->orderBy('day_number');
    }

    public function pickupPoints(): HasMany
    {
        return $this->hasMany(TourPickupPoint::class)->orderBy('sort_order');
    }

    public function slots(): HasMany
    {
        return $this->hasMany(TourSlot::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Only published tours are customer-visible (BR-090).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }
}
