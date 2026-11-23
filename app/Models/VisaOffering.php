<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisaOffering extends Model
{
    use BelongsToOrganization;
    use HasTranslations;
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'country_code',
        'visa_type',
        'name',
        'description',
        'price_cents',
        'expected_days_min',
        'expected_days_max',
        'offered_from',
        'offered_until',
        'issued_validity_days',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'description' => 'array',
            'price_cents' => 'integer',
            'offered_from' => 'datetime',
            'offered_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VisaOfferingDocument::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(VisaApplication::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('offered_from')->orWhere('offered_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('offered_until')->orWhere('offered_until', '>=', now()));
    }
}
