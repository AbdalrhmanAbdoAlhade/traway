<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hotel extends Model
{
    use BelongsToOrganization;
    use HasTranslations;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'country_code',
        'city',
        'address',
        'latitude',
        'longitude',
        'star_rating',
        'check_in_time',
        'check_out_time',
        'cover_image',
        'gallery',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'gallery' => 'array',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'rating_avg' => 'decimal:2',
            'published_at' => 'datetime',
        ];
    }

    public function roomTypes(): HasMany
    {
        return $this->hasMany(RoomType::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeInCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    /**
     * Map viewport search (BR-065).
     */
    public function scopeWithinBounds(Builder $query, float $minLat, float $maxLat, float $minLng, float $maxLng): Builder
    {
        return $query->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng]);
    }
}
