<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationApplication extends Model
{
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'type',
        'business_name',
        'country_code',
        'city',
        'responsible_name',
        'phone',
        'email',
        'status',
        'rejection_reason',
        'reapply_after',
        'terms_accepted_at',
        'reviewed_by',
        'reviewed_at',
        'organization_id',
        'activation_expires_at',
        'activation_used_at',
    ];

    protected $hidden = [
        'activation_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'reapply_after' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'activation_expires_at' => 'datetime',
            'activation_used_at' => 'datetime',
        ];
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(OrganizationDocument::class, 'application_id');
    }

    public function activationLinkIsValid(): bool
    {
        return $this->activation_used_at === null
            && $this->activation_expires_at !== null
            && $this->activation_expires_at->isFuture();
    }
}
