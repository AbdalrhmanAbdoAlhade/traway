<?php

namespace Tests\Concerns;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\TenantResolver;
use App\Services\Organization\RoleTemplateService;
use Database\Seeders\PlatformBootstrapSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds the real permission catalogue and builds organizations exactly the way
 * production does (role templates copied per organization, team-scoped roles).
 */
trait BuildsTenants
{
    protected Organization $platform;

    protected User $platformAdmin;

    protected Membership $platformAdminMembership;

    private int $tenantSequence = 0;

    protected function bootTenants(): void
    {
        $this->seed(PlatformBootstrapSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->platform = Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->firstOrFail();

        $this->platformAdmin = $this->createUser('platform-admin@traway.test');
        $this->platformAdminMembership = $this->createMembership($this->platformAdmin, $this->platform, owner: true);
        $this->attachRoleByName($this->platformAdmin, $this->platform, 'platform_super_admin');
    }

    protected function createUser(string $email, string $status = 'active'): User
    {
        return User::create([
            'name' => 'User '.$email,
            'email' => $email,
            'password' => 'password123',
            'status' => $status,
        ]);
    }

    protected function createOrganization(
        string $type = Organization::TYPE_COMPANY,
        ?string $name = null,
        string $status = Organization::STATUS_ACTIVE,
    ): Organization {
        $this->tenantSequence++;
        $name ??= ucfirst(str_replace('_', ' ', $type)).' '.$this->tenantSequence;

        $organization = Organization::create([
            'type' => $type,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->tenantSequence,
            'status' => $status,
        ]);

        app(RoleTemplateService::class)->provision($organization);

        return $organization;
    }

    protected function createMembership(
        User $user,
        Organization $organization,
        bool $owner = false,
        string $status = Membership::STATUS_ACTIVE,
    ): Membership {
        $membership = Membership::create([
            'user_id' => $user->getKey(),
            'organization_id' => $organization->getKey(),
            'status' => $status,
            'is_owner' => $owner,
            'accepted_at' => $status === Membership::STATUS_ACTIVE ? now() : null,
        ]);

        $this->tenantResolver()->forget($user);

        return $membership;
    }

    protected function attachRoleByName(User $user, Organization $organization, string $name): Role
    {
        $role = Role::query()
            ->where('name', $name)
            ->where('guard_name', 'web')
            ->where(config('permission.column_names.team_foreign_key', 'organization_id'), $organization->getKey())
            ->firstOrFail();

        DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
            (config('permission.column_names.role_pivot_key') ?? 'role_id') => $role->getKey(),
            'model_type' => $user->getMorphClass(),
            config('permission.column_names.model_morph_key', 'model_id') => $user->getKey(),
            config('permission.column_names.team_foreign_key', 'organization_id') => $organization->getKey(),
        ]);

        $this->tenantResolver()->forget($user);

        return $role;
    }

    protected function bindTokenTo(User $user, Membership $membership): void
    {
        $newToken = $user->createToken('test-token');
        $newToken->accessToken->forceFill(['membership_id' => $membership->getKey()])->save();

        $user->withAccessToken($newToken->accessToken);

        $this->tenantResolver()->forget($user);
    }

    protected function tenantResolver(): TenantResolver
    {
        return app(TenantResolver::class);
    }
}
