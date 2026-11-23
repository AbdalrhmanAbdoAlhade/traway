<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use SoftDeletes;

    public const TYPE_PLATFORM = 'platform';
    public const TYPE_COMPANY = 'company';
    public const TYPE_HOTEL = 'hotel';
    public const TYPE_SERVICE_PROVIDER = 'service_provider';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'type',
        'name',
        'legal_name',
        'slug',
        'status',
        'country_code',
        'city',
        'phone',
        'email',
        'commission_rate_bp',
        'approved_at',
        'approved_by',
        'suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate_bp' => 'integer',
            'approved_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    public function owner(): HasOne
    {
        return $this->hasOne(Membership::class)
            ->where('is_owner', true)
            ->where('status', Membership::STATUS_ACTIVE);
    }

    public function roles(): HasMany
    {
        return $this->hasMany(Role::class, config('permission.column_names.team_foreign_key', 'organization_id'));
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    public function visaOfferings(): HasMany
    {
        return $this->hasMany(VisaOffering::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(PartnerLedgerEntry::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function isPlatform(): bool
    {
        return $this->type === self::TYPE_PLATFORM;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Partner balance in cents. Negative balances carry forward automatically (DEC-10).
     */
    public function balanceCents(): int
    {
        return (int) $this->ledgerEntries()->sum('amount_cents');
    }
}
