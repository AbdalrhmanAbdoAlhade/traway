<?php

namespace App\Models\Concerns;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Tenant scope. Never applied globally: platform staff must be able to query across tenants,
     * so every tenant-facing query must call this explicitly (enforced in services / policies).
     */
    public function scopeForOrganization(Builder $query, int|Organization $organization): Builder
    {
        $id = $organization instanceof Organization ? $organization->getKey() : $organization;

        return $query->where($this->qualifyColumn('organization_id'), $id);
    }
}
