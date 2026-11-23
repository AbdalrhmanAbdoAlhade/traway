<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public const FAMILY_PLATFORM = 'platform';
    public const FAMILY_ORGANIZATION = 'organization';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'is_system' => 'boolean',
            'is_template' => 'boolean',
        ]);
    }

    /**
     * The Spatie "team" of this role (organization_id). NULL = global template.
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(
            Organization::class,
            config('permission.column_names.team_foreign_key', 'organization_id')
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isPlatform(): bool
    {
        return $this->family === self::FAMILY_PLATFORM;
    }

    public function isSystem(): bool
    {
        return (bool) $this->is_system;
    }
}
