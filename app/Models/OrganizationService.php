<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationService extends Model
{
    public const SERVICE_HOTELS = 'hotels';
    public const SERVICE_TOURS = 'tours';
    public const SERVICE_VISAS = 'visas';

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ENABLED = 'enabled';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_DISABLED = 'disabled';

    public const ALLOWED_SERVICES = [
        self::SERVICE_HOTELS,
        self::SERVICE_TOURS,
        self::SERVICE_VISAS,
    ];

    protected $fillable = [
        'organization_id',
        'service',
        'status',
        'requested_by_user_id',
        'requested_at',
        'reviewed_by_user_id',
        'reviewed_at',
        'rejection_reason',
        'enabled_at',
        'effective_at',
        'disabled_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'enabled_at' => 'datetime',
            'effective_at' => 'datetime',
            'disabled_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    public function isAcceptingNewIntake(): bool
    {
        return $this->isEnabled();
    }
}
