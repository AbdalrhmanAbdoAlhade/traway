<?php

namespace App\Services\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Organization lifecycle: creation, profile, suspension, closure and ownership.
 *
 * Every mutation is authorized through AuthorizationService (never a bare
 * permission string), runs in a transaction, and — when it changes who may act —
 * bumps the memberships' permission versions and revokes their access tokens.
 */
class OrganizationService
{
    /** @var array<int, string> */
    public const CREATABLE_TYPES = [
        Organization::TYPE_COMPANY,
        Organization::TYPE_HOTEL,
        Organization::TYPE_SERVICE_PROVIDER,
    ];

    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly TenantResolver $tenants,
        private readonly RoleTemplateService $templates,
    ) {}

    public function create(User $actor, array $attributes): Organization
    {
        $this->assertPlatformAllows($actor, 'platform.partners.create');

        return $this->createRecord($attributes);
    }

    /**
     * Internal creation used by platform approval. No authorization here: the
     * caller is responsible for checking the platform permission.
     *
     * @internal
     */
    public function createRecord(array $attributes): Organization
    {
        $type = $attributes['type'] ?? null;

        if (! in_array($type, self::CREATABLE_TYPES, true)) {
            throw new BusinessRuleException('Unsupported organization type.');
        }

        $name = trim((string) ($attributes['name'] ?? ''));

        if ($name === '') {
            throw new BusinessRuleException('An organization name is required.');
        }

        return DB::transaction(function () use ($attributes, $type, $name) {
            $organization = Organization::create([
                'type' => $type,
                'name' => $name,
                'legal_name' => $attributes['legal_name'] ?? null,
                'slug' => $this->uniqueSlug($name),
                'status' => $attributes['status'] ?? Organization::STATUS_PENDING,
                'country_code' => $attributes['country_code'] ?? null,
                'city' => $attributes['city'] ?? null,
                'phone' => $attributes['phone'] ?? null,
                'email' => $attributes['email'] ?? null,
                'commission_rate_bp' => (int) ($attributes['commission_rate_bp'] ?? 0),
            ]);

            $this->templates->provision($organization);

            return $organization;
        });
    }

    public function update(User $actor, Organization $organization, array $attributes): Organization
    {
        $allowed = $this->authz->allows($actor, 'org.profile.update', $organization)
            || $this->platformAllows($actor, 'platform.partners.update');

        if (! $allowed) {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $organization->fill(collect($attributes)->only([
            'name', 'legal_name', 'country_code', 'city', 'phone', 'email',
        ])->all());

        $organization->save();

        return $organization->refresh();
    }

    /**
     * Platform suspension. Access is cut immediately: every membership is
     * invalidated and every organization-bound token is revoked.
     */
    public function suspend(User $actor, Organization $organization, string $reason): Organization
    {
        $this->assertPlatformAllows($actor, 'platform.partners.suspend');

        if ($organization->isPlatform()) {
            throw new BusinessRuleException('The platform organization cannot be suspended.');
        }

        DB::transaction(function () use ($organization, $reason) {
            $organization->forceFill([
                'status' => Organization::STATUS_SUSPENDED,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
            ])->save();

            $this->cutTenantAccess($organization);
        });

        return $organization->refresh();
    }

    public function reinstate(User $actor, Organization $organization): Organization
    {
        $this->assertPlatformAllows($actor, 'platform.partners.suspend');

        if ($organization->isPlatform()) {
            throw new BusinessRuleException('The platform organization is always active.');
        }

        DB::transaction(function () use ($organization) {
            $organization->forceFill([
                'status' => Organization::STATUS_ACTIVE,
                'approved_at' => $organization->approved_at ?? now(),
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();

            $this->cutTenantAccess($organization);
        });

        return $organization->refresh();
    }

    /**
     * Permanent closure, owner only. Access is cut like a suspension, but the
     * organization cannot be reinstated.
     */
    public function close(User $actor, Organization $organization): Organization
    {
        $membership = $this->authz->activeMembership($actor, $organization);

        if ($membership === null || ! $membership->is_owner) {
            throw new AuthorizationException('Only the organization owner can close it.');
        }

        if ($organization->isPlatform()) {
            throw new BusinessRuleException('The platform organization cannot be closed.');
        }

        DB::transaction(function () use ($organization) {
            $organization->forceFill([
                'status' => Organization::STATUS_CLOSED,
                'suspended_at' => now(),
            ])->save();

            $this->cutTenantAccess($organization);
        });

        return $organization->refresh();
    }

    /**
     * Single-holder ownership transfer. The acting user must currently own the
     * organization; the target must be an active member of it.
     */
    public function transferOwnership(User $actor, Organization $organization, Membership $target): Organization
    {
        DB::transaction(function () use ($actor, $organization, $target) {
            $lockedOrganization = Organization::query()->lockForUpdate()->findOrFail($organization->getKey());

            $currentOwner = Membership::query()
                ->where('organization_id', $lockedOrganization->getKey())
                ->where('is_owner', true)
                ->where('status', Membership::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if ($currentOwner === null || (int) $currentOwner->user_id !== (int) $actor->getKey()) {
                throw new AuthorizationException('Only the current owner can transfer ownership.');
            }

            $lockedTarget = Membership::query()->lockForUpdate()->findOrFail($target->getKey());

            if ((int) $lockedTarget->organization_id !== (int) $lockedOrganization->getKey()) {
                throw new BusinessRuleException('The new owner must belong to this organization.');
            }

            if ($lockedTarget->status !== Membership::STATUS_ACTIVE) {
                throw new BusinessRuleException('The new owner must have an active membership.');
            }

            if ((int) $lockedTarget->getKey() === (int) $currentOwner->getKey()) {
                return;
            }

            $ownerRole = $this->templates->ownerRole($lockedOrganization);

            // Clear the current owner first: the database allows one active owner.
            $currentOwner->forceFill(['is_owner' => false])->save();
            $lockedTarget->forceFill(['is_owner' => true])->save();

            if ($ownerRole !== null) {
                $this->detachRole($currentOwner->user, $ownerRole, $lockedOrganization);
                $this->attachRole($lockedTarget->user, $ownerRole, $lockedOrganization);
            }

            foreach ([$currentOwner, $lockedTarget] as $membership) {
                $this->authz->invalidate($membership);
                $this->tenants->forget($membership->user);
            }
        });

        return $organization->refresh();
    }

    public function attachRole(User $user, Role $role, Organization $organization): void
    {
        DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
            (config('permission.column_names.role_pivot_key') ?? 'role_id') => $role->getKey(),
            'model_type' => $user->getMorphClass(),
            config('permission.column_names.model_morph_key', 'model_id') => $user->getKey(),
            config('permission.column_names.team_foreign_key', 'organization_id') => $organization->getKey(),
        ]);
    }

    public function detachRole(User $user, Role $role, Organization $organization): void
    {
        DB::table(config('permission.table_names.model_has_roles'))
            ->where((config('permission.column_names.role_pivot_key') ?? 'role_id'), $role->getKey())
            ->where('model_type', $user->getMorphClass())
            ->where(config('permission.column_names.model_morph_key', 'model_id'), $user->getKey())
            ->where(config('permission.column_names.team_foreign_key', 'organization_id'), $organization->getKey())
            ->delete();
    }

    private function cutTenantAccess(Organization $organization): void
    {
        $memberships = Membership::query()
            ->with('user')
            ->where('organization_id', $organization->getKey())
            ->get();

        foreach ($memberships as $membership) {
            $this->authz->invalidate($membership);

            if ($membership->user !== null) {
                $this->tenants->forget($membership->user);
            }

            if ($membership->status !== Membership::STATUS_ACTIVE) {
                continue;
            }

            PersonalAccessToken::query()
                ->where('membership_id', $membership->getKey())
                ->delete();
        }
    }

    private function assertPlatformAllows(User $actor, string $permission): void
    {
        if (! $this->platformAllows($actor, $permission)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    private function platformAllows(User $actor, string $permission): bool
    {
        $platform = Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();

        return $platform !== null && $this->authz->allows($actor, $permission, $platform);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'organization';
        $slug = $base;
        $suffix = 2;

        while (Organization::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
