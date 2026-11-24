<?php

namespace App\Services\Organization;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Global role templates live with organization_id = NULL and is_template = true.
 * Every organization gets its own copies (is_template = false, team = its id) so
 * an owner can tune an employee role without touching another tenant.
 *
 * A template is copied only when every permission it contains is allowed for the
 * organization type; the Owner template is chosen by name from
 * config/authorization.php. Copying is idempotent.
 */
class RoleTemplateService
{
    public function provision(Organization $organization): void
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $templates = Role::query()
            ->where('is_template', true)
            ->whereNull($teamKey)
            ->with('permissions')
            ->get();

        $ownerTemplates = config('authorization.protected_owner_templates', []);

        DB::transaction(function () use ($templates, $organization, $teamKey, $ownerTemplates) {
            foreach ($templates as $template) {
                if (! $this->appliesTo($template, $organization, $ownerTemplates)) {
                    continue;
                }

                $role = Role::query()->updateOrCreate(
                    [
                        'name' => $template->name,
                        'guard_name' => $template->guard_name,
                        $teamKey => $organization->getKey(),
                    ],
                    [
                        'family' => Role::FAMILY_ORGANIZATION,
                        'is_system' => $template->is_system,
                        'is_template' => false,
                        'description' => $template->description,
                    ]
                );

                $role->syncPermissions($template->permissions->pluck('name')->all());
            }
        });
    }

    /**
     * The organization's copy of its protected Owner role, if it was provisioned.
     */
    public function ownerRole(Organization $organization): ?Role
    {
        $name = config('authorization.protected_owner_templates.'.$organization->type);

        if ($name === null) {
            return null;
        }

        return Role::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->where(config('permission.column_names.team_foreign_key', 'organization_id'), $organization->getKey())
            ->first();
    }

    /**
     * @param  array<string, string>  $ownerTemplates
     */
    private function appliesTo(Role $template, Organization $organization, array $ownerTemplates): bool
    {
        if (isset($ownerTemplates[$template->name])) {
            return $ownerTemplates[$template->name] === $organization->type;
        }

        $permissions = $template->permissions;

        if ($permissions->isEmpty()) {
            return false;
        }

        return $permissions->every(function (Permission $permission) use ($organization) {
            return ! $permission->isPlatform()
                && $permission->allowedForOrganizationType($organization->type);
        });
    }
}
