<?php

namespace App\Services\Authorization;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Single entry point for every authorization decision.
 *
 * A permission string alone never grants access. Every decision evaluates:
 *   1. the effective permission (roles + direct membership grants),
 *   2. an active membership and its organization context,
 *   3. the service scope (flights / hotels / tours / visas / esim),
 *   4. the hotel property scope,
 *   5. resource ownership,
 *   6. the delegation boundary (non-delegable permissions stay with the owner).
 *
 * There is no global bypass: a platform super admin only holds platform
 * permissions inside the platform organization.
 */
class AuthorizationService
{
    private const CACHE_PREFIX = 'traway.authz.membership';

    public function __construct(private readonly PermissionRegistrar $registrar) {}

    /*
    |--------------------------------------------------------------------------
    | Team context
    |--------------------------------------------------------------------------
    */

    /**
     * Point Spatie at one organization. Required before any role lookup, because
     * roles and assignments are keyed by organization_id (Spatie Teams).
     */
    public function useTeam(Organization|int|null $organization): void
    {
        $id = $organization instanceof Organization ? $organization->getKey() : $organization;

        $this->registrar->setPermissionsTeamId($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Membership resolution
    |--------------------------------------------------------------------------
    */

    /**
     * The user's ACTIVE membership of an ACTIVE organization, with its scopes loaded.
     * Invited, suspended and removed memberships are never resolved, and neither
     * is a membership of a pending, suspended or closed organization. A suspended
     * user account can never act, whatever membership it holds.
     */
    public function activeMembership(User $user, Organization|int|null $organization): ?Membership
    {
        if ($organization === null || ! $user->isActive()) {
            return null;
        }

        $id = $organization instanceof Organization ? $organization->getKey() : $organization;

        return Membership::query()
            ->with(['organization', 'serviceScopes', 'hotelScopes'])
            ->where('user_id', $user->getKey())
            ->where('organization_id', $id)
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereHas('organization', fn ($query) => $query->where('status', Organization::STATUS_ACTIVE))
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Effective permissions
    |--------------------------------------------------------------------------
    */

    /**
     * Effective permission names: role permissions for THIS organization plus
     * direct membership grants. Cached per membership and permissions_version.
     *
     * @return array<int, string>
     */
    public function effectivePermissions(Membership $membership): array
    {
        return Cache::remember(
            $this->cacheKey($membership),
            now()->addMinutes(30),
            fn () => $this->loadEffectivePermissions($membership)
        );
    }

    /**
     * Invalidate the cached set. Called whenever roles, grants or scopes change.
     */
    public function flush(Membership $membership): void
    {
        Cache::forget($this->cacheKey($membership));
    }

    /**
     * Bump the permission version and drop the cache entry.
     */
    public function invalidate(Membership $membership): void
    {
        $membership->bumpPermissionsVersion();
        $membership->refresh();
        $this->flush($membership);
    }

    /**
     * @return array<int, string>
     */
    private function loadEffectivePermissions(Membership $membership): array
    {
        $user = $membership->user;

        if ($user === null) {
            return [];
        }

        $morphClass = $user->getMorphClass();

        // The role must belong to the SAME organization as the assignment: a forged
        // or migrated pivot row can never import another tenant's (or a template's)
        // permissions into this membership.
        $roleIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.organization_id', $membership->organization_id)
            ->where('roles.organization_id', $membership->organization_id)
            ->where('roles.guard_name', 'web')
            ->where('model_has_roles.model_type', $morphClass)
            ->where('model_has_roles.model_id', $user->getKey())
            ->pluck('model_has_roles.role_id');

        $rolePermissionIds = DB::table('role_has_permissions')
            ->whereIn('role_id', $roleIds)
            ->pluck('permission_id');

        $directPermissionIds = DB::table('membership_permissions')
            ->where('membership_id', $membership->getKey())
            ->pluck('permission_id');

        $permissionIds = $rolePermissionIds->merge($directPermissionIds)->unique()->values();

        if ($permissionIds->isEmpty()) {
            return [];
        }

        return Permission::query()
            ->whereIn('id', $permissionIds)
            ->get()
            ->filter(fn (Permission $permission) => $this->permissionBelongsToContext($permission, $membership))
            ->pluck('name')
            ->unique()
            ->values()
            ->all();
    }

    /**
     * A tenant never inherits platform permissions, and only the organization
     * types listed on the permission may hold it.
     */
    private function permissionBelongsToContext(Permission $permission, Membership $membership): bool
    {
        $organization = $membership->organization;

        if ($organization === null) {
            return false;
        }

        if ($permission->isPlatform() !== $organization->isPlatform()) {
            return false;
        }

        return $permission->allowedForOrganizationType($organization->type);
    }

    private function cacheKey(Membership $membership): string
    {
        return sprintf(
            '%s:%d:v%d',
            self::CACHE_PREFIX,
            $membership->getKey(),
            (int) $membership->permissions_version
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Decisions
    |--------------------------------------------------------------------------
    */

    /**
     * @param  array{service?:?string, hotel_id?:?int, resource?:?Model}  $context
     */
    public function allows(
        User $user,
        string $permission,
        Organization|int|null $organization,
        array $context = [],
    ): bool {
        $membership = $this->activeMembership($user, $organization);

        if ($membership === null) {
            return false;
        }

        if (! in_array($permission, $this->effectivePermissions($membership), true)) {
            return false;
        }

        $definition = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->first();

        if ($definition === null) {
            return false;
        }

        if (! $this->withinDelegationBoundary($definition, $membership)) {
            return false;
        }

        if (! $this->withinServiceScope($definition, $membership, $context['service'] ?? null)) {
            return false;
        }

        $resource = $context['resource'] ?? null;
        $hotelId = $context['hotel_id'] ?? null;

        if ($resource instanceof Model) {
            if (! $this->ownsResource($membership, $resource)) {
                return false;
            }

            $hotelId ??= $resource->getAttribute('hotel_id');
        }

        if (is_numeric($hotelId) && ! $this->withinHotelScope($membership, (int) $hotelId)) {
            return false;
        }

        return true;
    }

    /**
     * Non-delegable permissions live with the owner only.
     */
    private function withinDelegationBoundary(Permission $permission, Membership $membership): bool
    {
        if ($permission->isPlatform()) {
            return $membership->organization->isPlatform();
        }

        if ($permission->isDelegable()) {
            return true;
        }

        return (bool) $membership->is_owner;
    }

    /**
     * Employees restricted to some services never act outside them.
     */
    private function withinServiceScope(Permission $permission, Membership $membership, ?string $service): bool
    {
        if ($permission->scope_requirement !== 'service') {
            return true;
        }

        $scoped = $membership->services();

        if ($scoped === []) {
            return true;
        }

        if ($service === null) {
            return false;
        }

        return in_array($service, $scoped, true);
    }

    /**
     * Employees restricted to properties never act outside them.
     */
    private function withinHotelScope(Membership $membership, int $hotelId): bool
    {
        if ($membership->organization?->type !== Organization::TYPE_HOTEL) {
            return true;
        }

        return $membership->isRestrictedToHotel($hotelId);
    }

    /**
     * Resource ownership: cross-tenant access is always denied, and a resource
     * with no tenant owner (a platform-owned coupon or visa offering) is never
     * treated as belonging to a partner organization.
     */
    public function ownsResource(Membership $membership, Model $resource): bool
    {
        $organizationId = $resource->getAttribute('organization_id');

        if ($organizationId === null) {
            return false;
        }

        return (int) $organizationId === (int) $membership->organization_id;
    }

    /*
    |--------------------------------------------------------------------------
    | Delegation (granting permissions to employees)
    |--------------------------------------------------------------------------
    */

    /**
     * Can $granter hand $permission to $target?
     *
     * Rules: the granter must hold the permission themselves, the permission must
     * be delegable, the granter must not exceed the organization type, and nobody
     * may grant a permission they do not hold. Owners may grant any delegable
     * organization permission they hold.
     */
    public function canGrant(User $granter, string $permission, Membership $target): bool
    {
        $organization = $target->organization;

        if ($organization === null) {
            return false;
        }

        // Nobody grants into a membership that is not active in this tenant.
        if ($target->status !== Membership::STATUS_ACTIVE) {
            return false;
        }

        $granterMembership = $this->activeMembership($granter, $organization);

        if ($granterMembership === null) {
            return false;
        }

        $definition = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->first();

        if ($definition === null) {
            return false;
        }

        if (! $definition->isDelegable()) {
            return false;
        }

        if (! $definition->allowedForOrganizationType($organization->type)) {
            return false;
        }

        if (! in_array($permission, $this->effectivePermissions($granterMembership), true)) {
            return false;
        }

        // A service-scoped employee cannot widen someone else's scope.
        $granterScoped = $granterMembership->services();
        $targetScoped = $target->services();

        if ($granterScoped !== [] && array_diff($targetScoped, $granterScoped) !== []) {
            return false;
        }

        return true;
    }

    /**
     * The permissions a given actor may delegate inside one organization.
     *
     * @return array<int, string>
     */
    public function delegablePermissions(Membership $actor): array
    {
        return collect($this->effectivePermissions($actor))
            ->filter(function (string $name) use ($actor) {
                $definition = Permission::query()->where('name', $name)->where('guard_name', 'web')->first();

                return $definition !== null
                    && $definition->isDelegable()
                    && $definition->allowedForOrganizationType($actor->organization->type);
            })
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Platform counter
    |--------------------------------------------------------------------------
    */

    public function isPlatformStaff(User $user): bool
    {
        return $user->isPlatformStaff();
    }
}
