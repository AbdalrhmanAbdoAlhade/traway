<?php

namespace App\Services\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\MembershipServiceScope;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Employee lifecycle inside one organization.
 *
 * Invitations store only a SHA-256 hash of the token. Every operation is scoped
 * to the actor's organization (or performed by platform staff holding
 * platform.organization_access.manage), validates the delegation boundary and
 * bumps the target membership's permission version.
 */
class MembershipService
{
    private const INVITATION_TTL_DAYS = 7;

    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly TenantResolver $tenants,
        private readonly RoleTemplateService $templates,
    ) {}

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array<int, string>  $serviceScopes
     * @param  array<int, int>  $hotelIds
     */
    public function invite(
        User $actor,
        Organization $organization,
        User $invitee,
        ?Role $role = null,
        array $serviceScopes = [],
        array $hotelIds = [],
        ?string $jobTitle = null,
    ): InvitationResult {
        $this->assertCanManage($actor, $organization, 'employees.create');

        if ($organization->status !== Organization::STATUS_ACTIVE) {
            throw new BusinessRuleException('Only an active organization can invite employees.');
        }

        if (! $invitee->isActive()) {
            throw new BusinessRuleException('The invited account is not active.');
        }

        if ((int) $invitee->getKey() === (int) $actor->getKey()) {
            throw new BusinessRuleException('You cannot invite yourself.');
        }

        $existing = Membership::query()
            ->where('user_id', $invitee->getKey())
            ->where('organization_id', $organization->getKey())
            ->first();

        if ($existing !== null) {
            throw new BusinessRuleException(match ($existing->status) {
                Membership::STATUS_INVITED => 'This person already has a pending invitation.',
                Membership::STATUS_ACTIVE => 'This person is already a member of the organization.',
                Membership::STATUS_SUSPENDED => 'This person has a suspended membership; reinstate it instead.',
                default => 'This person was removed from the organization and cannot be re-invited.',
            });
        }

        $token = Str::random(64);

        return DB::transaction(function () use (
            $actor, $organization, $invitee, $role, $serviceScopes, $hotelIds, $jobTitle, $token
        ) {
            $membership = Membership::create([
                'user_id' => $invitee->getKey(),
                'organization_id' => $organization->getKey(),
                'status' => Membership::STATUS_INVITED,
                'is_owner' => false,
                'job_title' => $jobTitle,
                'invited_by' => $actor->getKey(),
                'invitation_token_hash' => $this->hashToken($token),
                'invitation_expires_at' => now()->addDays(self::INVITATION_TTL_DAYS),
                'invited_at' => now(),
            ]);

            $this->syncScopes($membership, $serviceScopes, $hotelIds);

            if ($role !== null) {
                $this->assertRoleAssignable($actor, $membership, $role);
                $this->attachRole($membership, $role);
            }

            $this->authz->invalidate($membership);
            $this->tenants->forget($invitee);

            return new InvitationResult($membership->refresh(), $token);
        });
    }

    public function acceptInvitation(User $user, string $plainToken): Membership
    {
        $membership = Membership::query()
            ->with('organization')
            ->where('invitation_token_hash', $this->hashToken($plainToken))
            ->first();

        if ($membership === null || $membership->status !== Membership::STATUS_INVITED) {
            throw new BusinessRuleException('This invitation is invalid or has already been used.');
        }

        if ((int) $membership->user_id !== (int) $user->getKey()) {
            throw new AuthorizationException('This invitation belongs to another account.');
        }

        if (! $user->isActive()) {
            throw new AuthorizationException('This account is not active.');
        }

        if ($membership->invitation_expires_at === null || $membership->invitation_expires_at->isPast()) {
            throw new BusinessRuleException('This invitation has expired.');
        }

        if ($membership->organization?->status !== Organization::STATUS_ACTIVE) {
            throw new BusinessRuleException('The organization is not active.');
        }

        $membership->forceFill([
            'status' => Membership::STATUS_ACTIVE,
            'accepted_at' => now(),
            'invitation_token_hash' => null,
            'invitation_expires_at' => null,
        ])->save();

        $this->authz->invalidate($membership);
        $this->tenants->forget($user);

        return $membership->refresh();
    }
    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Membership>
     */
    public function listPendingInvitations(User $actor, Organization $organization)
    {
        $this->assertCanManage($actor, $organization, 'employees.create');

        return Membership::query()
            ->with(['user', 'inviter', 'serviceScopes', 'hotelScopes'])
            ->where('organization_id', $organization->getKey())
            ->where('status', Membership::STATUS_INVITED)
            ->latest('invited_at')
            ->get();
    }

    /**
     * Rotates the token: the previous link stops working immediately and the
     * expiry window restarts. Works on expired invitations too.
     */
    public function resendInvitation(User $actor, Organization $organization, int $invitationId): InvitationResult
    {
        $this->assertCanManage($actor, $organization, 'employees.create');

        if ($organization->status !== Organization::STATUS_ACTIVE) {
            throw new BusinessRuleException('Only an active organization can invite employees.');
        }

        $membership = $this->findPendingInvitation($organization, $invitationId);

        if (! $membership->user->isActive()) {
            throw new BusinessRuleException('The invited account is not active.');
        }

        $token = Str::random(64);

        $membership->forceFill([
            'invitation_token_hash' => $this->hashToken($token),
            'invitation_expires_at' => now()->addDays(self::INVITATION_TTL_DAYS),
            'invited_at' => now(),
            'invited_by' => $actor->getKey(),
        ])->save();

        return new InvitationResult($membership->refresh(), $token);
    }

    public function revokeInvitation(User $actor, Organization $organization, int $invitationId): void
    {
        $this->assertCanManage($actor, $organization, 'employees.create');

        $membership = $this->findPendingInvitation($organization, $invitationId);

        DB::transaction(fn () => $this->deleteInvitedMembership($membership));
    }

    public function declineInvitation(User $user, string $plainToken): void
    {
        $membership = Membership::query()
            ->where('invitation_token_hash', $this->hashToken($plainToken))
            ->first();

        if ($membership === null || $membership->status !== Membership::STATUS_INVITED) {
            throw new BusinessRuleException('This invitation is invalid or has already been used.');
        }

        if ((int) $membership->user_id !== (int) $user->getKey()) {
            throw new AuthorizationException('This invitation belongs to another account.');
        }

        DB::transaction(fn () => $this->deleteInvitedMembership($membership));
    }
    /*
    |--------------------------------------------------------------------------
    | Roles and direct grants
    |--------------------------------------------------------------------------
    */

    public function assignRole(User $actor, Membership $target, Role $role): void
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'roles.assign');
        $this->assertRoleAssignable($actor, $target, $role);

        DB::transaction(function () use ($target, $role) {
            $this->attachRole($target, $role);
            $this->authz->invalidate($target);
            $this->tenants->forget($target->user);
        });
    }

    public function revokeRole(User $actor, Membership $target, Role $role): void
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'roles.assign');

        if ($role->isSystem()) {
            throw new BusinessRuleException('System roles cannot be revoked individually; change the member\'s role instead.');
        }

        $this->detachRole($target, $role);

        $this->authz->invalidate($target);
        $this->tenants->forget($target->user);
    }

    public function grantPermission(User $actor, Membership $target, string $permissionName): void
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'employees.update');

        $permission = Permission::query()
            ->where('name', $permissionName)
            ->where('guard_name', 'web')
            ->firstOrFail();

        if ($this->isPlatformManager($actor, $organization)) {
            if ($permission->isPlatform()
                || ! $permission->isDelegable()
                || ! $permission->allowedForOrganizationType($organization->type)) {
                throw new AuthorizationException('You cannot grant this permission.');
            }
        } elseif (! $this->authz->canGrant($actor, $permissionName, $target)) {
            throw new AuthorizationException('You cannot grant this permission.');
        }

        DB::table('membership_permissions')->insertOrIgnore([
            'membership_id' => $target->getKey(),
            'permission_id' => $permission->getKey(),
            'granted_by_user_id' => $actor->getKey(),
            'granted_by_membership_id' => $this->authz->activeMembership($actor, $organization)?->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authz->invalidate($target);
        $this->tenants->forget($target->user);
    }

    public function revokePermission(User $actor, Membership $target, string $permissionName): void
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'employees.update');

        $permission = Permission::query()
            ->where('name', $permissionName)
            ->where('guard_name', 'web')
            ->firstOrFail();

        DB::table('membership_permissions')
            ->where('membership_id', $target->getKey())
            ->where('permission_id', $permission->getKey())
            ->delete();

        $this->authz->invalidate($target);
        $this->tenants->forget($target->user);
    }

    /**
     * @param  array<int, string>  $serviceScopes
     * @param  array<int, int>  $hotelIds
     */
    public function updateScopes(
        User $actor,
        Membership $target,
        array $serviceScopes,
        array $hotelIds,
    ): Membership {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'employees.update');

        DB::transaction(function () use ($target, $serviceScopes, $hotelIds) {
            $this->syncScopes($target, $serviceScopes, $hotelIds);
            $this->authz->invalidate($target);
            $this->tenants->forget($target->user);
        });

        return $target->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Suspension, removal, reinstatement
    |--------------------------------------------------------------------------
    */

    public function suspend(User $actor, Membership $target): Membership
    {
        $this->assertDeactivatable($actor, $target);

        $target->forceFill([
            'status' => Membership::STATUS_SUSPENDED,
            'suspended_at' => now(),
        ])->save();

        $this->cutAccess($target);

        return $target->refresh();
    }

    public function reinstate(User $actor, Membership $target): Membership
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'employees.deactivate');

        if ($target->status !== Membership::STATUS_SUSPENDED) {
            throw new BusinessRuleException('Only a suspended membership can be reinstated.');
        }

        $target->forceFill([
            'status' => Membership::STATUS_ACTIVE,
            'suspended_at' => null,
            'accepted_at' => $target->accepted_at ?? now(),
        ])->save();

        $this->authz->invalidate($target);
        $this->tenants->forget($target->user);

        return $target->refresh();
    }

       public function remove(User $actor, Membership $target): Membership
    {
        $organization = $this->targetOrganization($target);

        $this->assertDeactivatable($actor, $target);

        DB::transaction(function () use ($target, $organization) {
            $this->retire($target, $organization);
        });

        return $target->refresh();
    }

    /*
    |--------------------------------------------------------------------------
    | Cross-organization transfer (remove + create, never an UPDATE of organization_id)
    |--------------------------------------------------------------------------
    */

    /**
     * Moves a non-owner employee to another organization. The membership in the
     * source organization is removed (history stays on the old row) and a brand
     * new membership is created in the destination with no roles and no scopes,
     * so the employee has no access there until the destination grants it.
     * Allowed for platform staff with access management, or for users who own
     * both organizations. Returns the NEW membership.
     */
    public function transfer(User $actor, Membership $target, Organization $destination): Membership
    {
        $source = $this->targetOrganization($target);

        if ((int) $source->getKey() === (int) $destination->getKey()) {
            throw new BusinessRuleException('The employee already belongs to that organization.');
        }

        $this->assertTransferAllowed($actor, $source, $destination);

        if ($target->is_owner) {
            throw new BusinessRuleException('Transfer ownership instead of transferring an owner membership.');
        }

        if ($target->status !== Membership::STATUS_ACTIVE) {
            throw new BusinessRuleException('Only an active membership can be transferred.');
        }

        if ($destination->status !== Organization::STATUS_ACTIVE) {
            throw new BusinessRuleException('The destination organization is not active.');
        }

        $duplicate = Membership::query()
            ->where('user_id', $target->user_id)
            ->where('organization_id', $destination->getKey())
            ->exists();

        if ($duplicate) {
            throw new BusinessRuleException('This person already has a membership in the destination organization.');
        }

        return DB::transaction(function () use ($actor, $target, $source, $destination) {
            $user = $target->user;

            $this->retire($target, $source);

            $new = Membership::create([
                'user_id' => $target->user_id,
                'organization_id' => $destination->getKey(),
                'status' => Membership::STATUS_ACTIVE,
                'is_owner' => false,
                'job_title' => $target->job_title,
                'invited_by' => $actor->getKey(),
                'invited_at' => now(),
                'accepted_at' => now(),
            ]);

            $this->authz->invalidate($new);
            $this->tenants->forget($user);

            return $new->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    private function assertDeactivatable(User $actor, Membership $target): void
    {
        $organization = $this->targetOrganization($target);

        $this->assertCanManage($actor, $organization, 'employees.deactivate');

        if ((int) $actor->getKey() === (int) $target->user_id) {
            throw new BusinessRuleException('You cannot deactivate your own membership.');
        }

        if ($target->is_owner) {
            throw new BusinessRuleException('The last owner cannot be suspended or removed. Transfer ownership first.');
        }
    }

    private function assertTransferAllowed(User $actor, Organization $source, Organization $destination): void
    {
        if ($this->platformAllows($actor, 'platform.organization_access.manage')) {
            return;
        }

        $sourceMembership = $this->authz->activeMembership($actor, $source);
        $destinationMembership = $this->authz->activeMembership($actor, $destination);

        if ($sourceMembership?->is_owner && $destinationMembership?->is_owner) {
            return;
        }

        throw new AuthorizationException('This action is unauthorized.');
    }

    private function assertRoleAssignable(User $actor, Membership $target, Role $role): void
    {
        if ((int) $role->organization_id !== (int) $target->organization_id) {
            throw new BusinessRuleException('The role belongs to another organization.');
        }

        if ($role->family !== Role::FAMILY_ORGANIZATION || $role->is_template) {
            throw new BusinessRuleException('Templates and platform roles cannot be assigned to employees.');
        }

        $ownerTemplateNames = array_keys(config('authorization.protected_owner_templates', []));

        if (in_array($role->name, $ownerTemplateNames, true)) {
            throw new BusinessRuleException('Ownership is transferred, not assigned as a role.');
        }

        $actorMembership = $this->authz->activeMembership($actor, $target->organization_id);
        $platformManager = $this->isPlatformManager($actor, $target->organization);

        if ($actorMembership === null && ! $platformManager) {
            throw new AuthorizationException('You cannot assign roles in this organization.');
        }

        $actorPermissions = $actorMembership === null
            ? []
            : $this->authz->effectivePermissions($actorMembership);

        $role->loadMissing('permissions');

        foreach ($role->permissions as $permission) {
            if ($permission->isPlatform()
                || ! $permission->isDelegable()
                || ! $permission->allowedForOrganizationType($target->organization->type)) {
                throw new AuthorizationException('You cannot assign a role that exceeds your own permissions.');
            }

            if (! $platformManager && ! in_array($permission->name, $actorPermissions, true)) {
                throw new AuthorizationException('You cannot assign a role that exceeds your own permissions.');
            }
        }
        if ($actorMembership !== null && ! $actorMembership->is_owner) {
            if (array_diff($target->services(), $actorMembership->services()) !== []) {
                throw new AuthorizationException('You cannot grant a service scope you do not hold.');
            }

            if (array_diff($target->hotelIds(), $actorMembership->hotelIds()) !== []) {
                throw new AuthorizationException('You cannot grant a property scope you do not hold.');
            }
        }

    private function assertCanManage(User $actor, Organization $organization, string $permission): void
    {
        if ($this->authz->allows($actor, $permission, $organization)) {
            return;
        }

        if (! $organization->isPlatform() && $this->platformAllows($actor, 'platform.organization_access.manage')) {
            return;
        }

        throw new AuthorizationException('This action is unauthorized.');
    }

    /**
     * Platform staff with access management may administer any partner tenant,
     * but never receive the tenant's own permissions and never touch the
     * platform tenant through this path.
     */
    private function isPlatformManager(User $actor, Organization $organization): bool
    {
        return ! $organization->isPlatform()
            && $this->platformAllows($actor, 'platform.organization_access.manage');
    }

    private function platformAllows(User $actor, string $permission): bool
    {
        $platform = Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();

        return $platform !== null && $this->authz->allows($actor, $permission, $platform);
    }

    private function targetOrganization(Membership $target): Organization
    {
        $organization = $target->organization;

        if ($organization === null) {
            throw new BusinessRuleException('The membership has no organization.');
        }

        return $organization;
    }

    /**
     * @param  array<int, string>  $serviceScopes
     * @param  array<int, int>  $hotelIds
     */
    private function syncScopes(Membership $membership, array $serviceScopes, array $hotelIds): void
    {
        $validServices = MembershipServiceScope::services();
        $serviceScopes = array_values(array_unique($serviceScopes));

        foreach ($serviceScopes as $service) {
            if (! in_array($service, $validServices, true)) {
                throw new BusinessRuleException("Unknown service scope: {$service}.");
            }
        }

        $hotelIds = array_values(array_unique(array_map('intval', $hotelIds)));

        if ($hotelIds !== []) {
            $owned = Hotel::query()
                ->whereIn('id', $hotelIds)
                ->where('organization_id', $membership->organization_id)
                ->count();

            if ($owned !== count($hotelIds)) {
                throw new BusinessRuleException('A property scope points at a hotel owned by another organization.');
            }
        }

        $membership->serviceScopes()->delete();
        $membership->hotelScopes()->delete();

        foreach ($serviceScopes as $service) {
            $membership->serviceScopes()->create(['service' => $service]);
        }

        foreach ($hotelIds as $hotelId) {
            $membership->hotelScopes()->create(['hotel_id' => $hotelId]);
        }

        $membership->unsetRelation('serviceScopes')->unsetRelation('hotelScopes');
    }

    private function attachRole(Membership $membership, Role $role): void
    {
        DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
            (config('permission.column_names.role_pivot_key') ?? 'role_id') => $role->getKey(),
            'model_type' => $membership->user->getMorphClass(),
            config('permission.column_names.model_morph_key', 'model_id') => $membership->user_id,
            config('permission.column_names.team_foreign_key', 'organization_id') => $membership->organization_id,
        ]);
    }

    private function detachRole(Membership $membership, Role $role): void
    {
        DB::table(config('permission.table_names.model_has_roles'))
            ->where((config('permission.column_names.role_pivot_key') ?? 'role_id'), $role->getKey())
            ->where('model_type', $membership->user->getMorphClass())
            ->where(config('permission.column_names.model_morph_key', 'model_id'), $membership->user_id)
            ->where(config('permission.column_names.team_foreign_key', 'organization_id'), $membership->organization_id)
            ->delete();
    }

    private function removeRoles(Membership $membership, Organization $organization): void
    {
        DB::table(config('permission.table_names.model_has_roles'))
            ->where('model_type', $membership->user->getMorphClass())
            ->where(config('permission.column_names.model_morph_key', 'model_id'), $membership->user_id)
            ->where(config('permission.column_names.team_foreign_key', 'organization_id'), $organization->getKey())
            ->delete();
    }

    private function cutAccess(Membership $membership): void
    {
        $this->authz->invalidate($membership);
        $this->tenants->forget($membership->user);

        PersonalAccessToken::query()
            ->where('membership_id', $membership->getKey())
            ->delete();
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
