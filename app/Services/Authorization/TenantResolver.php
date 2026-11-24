<?php

namespace App\Services\Authorization;

use App\Models\Membership;
use App\Models\User;

/**
 * Resolves which organization a user is acting inside for the current request.
 *
 * Order of precedence:
 *   1. the membership bound to the access token (personal_access_tokens.membership_id),
 *   2. an explicit X-Organization-Id header, validated against an active membership,
 *   3. the user's only active membership.
 *
 * Any candidate is re-validated through AuthorizationService::activeMembership(),
 * so a suspended membership, a suspended organization and a suspended user are
 * always rejected. There is no "first membership wins" guess when a user belongs
 * to several organizations: such a request must name its organization.
 */
class TenantResolver
{
    public const ORGANIZATION_HEADER = 'X-Organization-Id';

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
        $membership = $this->membershipFor($user);

        return $membership?->organization_id;
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
        $request = $this->currentRequest();

        $tokenMembershipId = $user->currentAccessToken()?->getAttribute('membership_id')
            ?? $request?->attributes->get('membership_id');

        if (is_numeric($tokenMembershipId)) {
            $membership = Membership::query()->find((int) $tokenMembershipId);

            if ($membership !== null && (int) $membership->user_id === (int) $user->getKey()) {
                $active = $this->authz->activeMembership($user, $membership->organization_id);

                if ($active !== null) {
                    return $active;
                }
            }
        }

        $header = $request?->header(self::ORGANIZATION_HEADER);

        if (is_string($header) && ctype_digit($header)) {
            $active = $this->authz->activeMembership($user, (int) $header);

            if ($active !== null) {
                return $active;
            }
        }

        $active = Membership::query()
            ->with(['organization', 'serviceScopes', 'hotelScopes'])
            ->where('user_id', $user->getKey())
            ->where('status', Membership::STATUS_ACTIVE)
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->limit(2)
            ->get();

        return $active->count() === 1 ? $active->first() : null;
    }

    private function currentRequest(): mixed
    {
        return app()->bound('request') ? request() : null;
    }
}
