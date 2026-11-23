<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use BelongsToOrganization;
    use SoftDeletes;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    // usage_count is incremented only by an atomic conditional UPDATE in the redemption service.
    protected $fillable = [
        'organization_id',
        'code',
        'campaign_name',
        'service_scope',
        'discount_type',
        'discount_value',
        'starts_at',
        'ends_at',
        'max_usage',
        'max_uses_per_customer',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'discount_value' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'max_usage' => 'integer',
            'usage_count' => 'integer',
            'max_uses_per_customer' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Code is unique per issuer: platform = scope 0, organization = its id.
        static::saving(function (Coupon $coupon) {
            $coupon->code = strtoupper(trim($coupon->code));
            $coupon->scope_key = $coupon->organization_id ?? 0;
        });
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function isWithinWindow(): bool
    {
        return ($this->starts_at === null || $this->starts_at->isPast())
            && ($this->ends_at === null || $this->ends_at->isFuture());
    }

    public function hasRemainingUsage(): bool
    {
        return $this->max_usage === null || $this->usage_count < $this->max_usage;
    }

    public function appliesToService(string $service): bool
    {
        return $this->service_scope === null || $this->service_scope === $service;
    }
}
