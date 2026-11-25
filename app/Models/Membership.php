<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Membership extends Model
{
    public const STATUS_INVITED = 'invited';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_SUSPENDED = 'suspended';

    public const STATUS_REMOVED = 'removed';

    // permissions_version and active_owner_org_id are managed by the model / services only.
    protected $fillable = [
        'user_id',
        'organization_id',
        'status',
        'is_owner',
        'job_title',
        'invited_by',
        'invitation_token_hash',
        'invitation_expires_at',
        'invited_at',
        'accepted_at',
        'suspended_at',
        'removed_at',
    ];

    protected $hidden = [
        'invitation_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'permissions_version' => 'integer',
            'invitation_expires_at' => 'datetime',
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
            'suspended_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Keeps the DB-level "one active owner per organization" constraint in sync.
        static::saving(function (Membership $membership) {
            $membership->active_owner_org_id =
                ($membership->is_owner && $membership->status === self::STATUS_ACTIVE)
                    ? $membership->organization_id
                    : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function serviceScopes(): HasMany
    {
        return $this->hasMany(MembershipServiceScope::class);
    }

    /**
     * Hotel properties this employee may access. No rows = NO property access
     * (G0-DEC-05). Owners reach every property through ownership, never through
     * an empty scope set.
     */
    public function hotelScopes(): HasMany
    {
        return $this->hasMany(MembershipHotelScope::class);
    }

    /**
     * @return array<int, int>
     */
    public function hotelIds(): array
    {
        return array_map('intval', $this->hotelScopes->pluck('hotel_id')->all());
    }

    /**
     * Fail closed: an owner reaches every property; anyone else only the
     * properties they have an explicit row for.
     */
    public function canAccessHotel(int $hotelId): bool
    {
        return (bool) $this->is_owner || in_array($hotelId, $this->hotelIds(), true);
    }

    /**
     * Fail closed: an owner reaches every service; anyone else only the
     * services they have an explicit row for.
     */
    public function canAccessService(string $service): bool
    {
        return (bool) $this->is_owner || in_array($service, $this->services(), true);
    }

    /**
     * Direct (per-employee) permission grants.
     */
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'membership_permissions', 'membership_id', 'permission_id')
            ->withPivot(['granted_by_user_id', 'granted_by_membership_id'])
            ->withTimestamps();
    }

    public function assignedBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'assigned_membership_id');
    }

    public function assignedVisaApplications(): HasMany
    {
        return $this->hasMany(VisaApplication::class, 'assigned_membership_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * @return array<int, string>
     */
    public function services(): array
    {
        return $this->serviceScopes->pluck('service')->all();
    }

    /**
     * Invalidates the cached effective permission set (PE-12).
     */
    public function bumpPermissionsVersion(): void
    {
        $this->increment('permissions_version');
    }
}
