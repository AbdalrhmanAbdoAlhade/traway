<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Booking extends Model
{
    use BelongsToOrganization;

    public const SERVICE_FLIGHT = 'flight';

    public const SERVICE_HOTEL = 'hotel';

    public const SERVICE_TOUR = 'tour';

    public const SERVICE_ESIM = 'esim';

    public const SERVICE_VISA = 'visa';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'reference',
        'service',
        'user_id',
        'organization_id',
        'hotel_id',
        'assigned_membership_id',
        'checkout_session_id',
        'status',
        'provider',
        'provider_reference',
        'subtotal_cents',
        'taxes_cents',
        'fees_cents',
        'discount_cents',
        'total_cents',
        'currency',
        'coupon_id',
        'coupon_code',
        'commission_rate_bp',
        'commission_cents',
        'partner_net_cents',
        'contact_name',
        'contact_email',
        'contact_phone',
        'expires_at',
        'confirmed_at',
        'completed_at',
        'cancelled_at',
        'anonymized_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_cents' => 'integer',
            'taxes_cents' => 'integer',
            'fees_cents' => 'integer',
            'discount_cents' => 'integer',
            'total_cents' => 'integer',
            'commission_rate_bp' => 'integer',
            'commission_cents' => 'integer',
            'partner_net_cents' => 'integer',
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Hotel property this booking belongs to (hotel service only).
     */
    public function hotelProperty(): BelongsTo
    {
        return $this->belongsTo(Hotel::class, 'hotel_id');
    }

    public function assignedMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'assigned_membership_id');
    }

    public function checkoutSession(): BelongsTo
    {
        return $this->belongsTo(CheckoutSession::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function redemption(): HasOne
    {
        return $this->hasOne(CouponRedemption::class);
    }

    public function flight(): HasOne
    {
        return $this->hasOne(FlightBooking::class);
    }

    public function hotel(): HasOne
    {
        return $this->hasOne(HotelBooking::class);
    }

    public function tour(): HasOne
    {
        return $this->hasOne(TourBooking::class);
    }

    public function esim(): HasOne
    {
        return $this->hasOne(EsimOrder::class);
    }

    public function visaApplication(): HasOne
    {
        return $this->hasOne(VisaApplication::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'subject');
    }

    public function scopeOfService(Builder $query, string $service): Builder
    {
        return $query->where('service', $service);
    }

    /**
     * Assigned-only employees (BR-087, BR-115).
     */
    public function scopeAssignedTo(Builder $query, int $membershipId): Builder
    {
        return $query->where('assigned_membership_id', $membershipId);
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }
}
