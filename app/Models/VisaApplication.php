<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VisaApplication extends Model
{
    use BelongsToOrganization;

    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_DOCUMENTS_REQUIRED = 'documents_required';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'booking_id',
        'user_id',
        'visa_offering_id',
        'organization_id',
        'assigned_membership_id',
        'status',
        'applicant_name',
        'passport_number',
        'passport_expires_at',
        'nationality',
        'price_cents',
        'expected_days_min',
        'expected_days_max',
        'visa_valid_from',
        'visa_valid_until',
        'rejection_reason',
        'decided_by',
        'decided_at',
        'submitted_at',
        'anonymized_at',
    ];

    protected $hidden = [
        'passport_number',
    ];

    protected function casts(): array
    {
        return [
            'passport_number' => 'encrypted',
            'passport_expires_at' => 'date',
            'price_cents' => 'integer',
            'visa_valid_from' => 'date',
            'visa_valid_until' => 'date',
            'decided_at' => 'datetime',
            'submitted_at' => 'datetime',
            'anonymized_at' => 'datetime',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(VisaOffering::class, 'visa_offering_id');
    }

    public function assignedMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'assigned_membership_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VisaApplicationDocument::class);
    }

    public function statusHistories(): MorphMany
    {
        return $this->morphMany(StatusHistory::class, 'subject');
    }

    public function scopeAssignedTo(Builder $query, int $membershipId): Builder
    {
        return $query->where('assigned_membership_id', $membershipId);
    }

    /**
     * Statuses that block account deletion (DEC-09: visa still under review).
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_SUBMITTED,
            self::STATUS_UNDER_REVIEW,
            self::STATUS_DOCUMENTS_REQUIRED,
            self::STATUS_PROCESSING,
        ]);
    }
}
