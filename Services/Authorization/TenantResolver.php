<?php

namespace App\Services\Authorization;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Resolves which organization a user is acting inside for the current request.
 *
 * Order of precedence:
 *   1. the membership bound to the access token (personal_access_tokens.membership_id).
 *      A bound token FAILS CLOSED: if that membership (or its organization) is no
 *      longer active, the request has no tenant. It never falls through to guessing.
 *   2. an unbound token: the user's only active membership.
 *
 * There is no request header or any other client-controlled input that can pick
 * an organization. A user with several memberships must obtain a bound token
 * through AuthService::switchOrganization().
 */
class TenantResolver
{
    /** @var array<int, Membership|null> */
    private array $resolved = [];

    public function __construct(private readonly AuthorizationService $authz) {}

    public function membershipFor(User $user): ?Membership
    {
        $key = (int) $user->getKey();

        if (array_key_exists($key, $this->resolved)) {
            return $this->resolved[$key];
        }

        return $this->resolved[$key] = $this->resolve($user);
    }

    public function organizationIdFor(User $user): ?int
    {
        return $this->membershipFor($user)?->organization_id;
    }

    /**
     * Drop the memoized answer after a membership/organization change so the rest
     * of the same request cannot rely on stale tenant state.
     */
    public function forget(User $user): void
    {
        unset($this->resolved[(int) $user->getKey()]);
    }

    private function resolve(User $user): ?Membership
    {
        $token = $user->currentAccessToken();

        $tokenMembershipId = $token instanceof Model
            ? $token->getAttribute('membership_id')
            : null;

        if ($tokenMembershipId !== null) {
            $membership = Membership::query()->find((int) $tokenMembershipId);

            if ($membership === null || (int) $membership->user_id !== (int) $user->getKey()) {
                return null;
            }

            return $this->authz->activeMembership($user, $membership->organization_id);
        }

        $active = Membership::query()
            ->with(['organization', 'serviceScopes', 'hotelScopes'])
            ->where('user_id', $user->getKey())
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereHas('organization', fn ($query) => $query->where('status', Organization::STATUS_ACTIVE))
            ->limit(2)
            ->get();

        return $active->count() === 1 ? $active->first() : null;
    }
}
