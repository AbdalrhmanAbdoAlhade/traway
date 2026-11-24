<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Database\Eloquent\Model;

/**
 * Base class for every resource policy.
 *
 * A policy never trusts a bare permission string: the decision is delegated to
 * AuthorizationService with the actor's resolved tenant, so the active
 * membership, organization status, organization type, service scope, hotel
 * property scope, resource ownership and delegation boundary are all enforced
 * in one place. Platform permissions are evaluated only inside the platform
 * organization; there is no global bypass.
 */
abstract class TenantPolicy
{
    public function __construct(
        protected readonly AuthorizationService $authz,
        protected readonly TenantResolver $tenants,
    ) {}

    protected function membership(User $user): ?Membership
    {
        return $this->tenants->membershipFor($user);
    }

    /**
     * @param  array{service?:?string, hotel_id?:?int, resource?:?Model}  $context
     */
    protected function allows(User $user, string $permission, array $context = []): bool
    {
        $membership = $this->membership($user);

        if ($membership === null) {
            return false;
        }

        return $this->authz->allows($user, $permission, $membership->organization_id, $context);
    }

    /**
     * @param  array{service?:?string, hotel_id?:?int, resource?:?Model}  $context
     */
    protected function platform(User $user, string $permission, array $context = []): bool
    {
        $organization = $this->platformOrganization();

        if ($organization === null) {
            return false;
        }

        return $this->authz->allows($user, $permission, $organization, $context);
    }

    protected function platformOrganization(): ?Organization
    {
        return Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();
    }

    protected function isMemberOf(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user);

        return $membership !== null
            && (int) $membership->organization_id === (int) $organization->getKey();
    }

    protected function isOwnerOf(User $user, Organization $organization): bool
    {
        $membership = $this->membership($user);

        return $membership !== null
            && (int) $membership->organization_id === (int) $organization->getKey()
            && (bool) $membership->is_owner;
    }
}
