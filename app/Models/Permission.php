<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public const FAMILY_PLATFORM = 'platform';
    public const FAMILY_ORGANIZATION = 'organization';

    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'allowed_org_types' => 'array',
            'is_delegable' => 'boolean',
        ]);
    }

    public function isPlatform(): bool
    {
        return $this->family === self::FAMILY_PLATFORM;
    }

    /**
     * Can an organization of this type hold the permission? NULL list = every type.
     */
    public function allowedForOrganizationType(string $type): bool
    {
        return empty($this->allowed_org_types) || in_array($type, $this->allowed_org_types, true);
    }
}
