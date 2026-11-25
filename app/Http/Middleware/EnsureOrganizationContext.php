<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tenant boundary for every route that carries {organization}.
 *
 * The organization in the URL must be the one the caller's token is bound to.
 * The only exception is platform staff holding platform.organization_access.manage,
 * who may administer partner tenants from the platform organization.
 * Fails closed: no resolvable organization means no access.
 */
class EnsureOrganizationContext
{
    public function __construct(
        private readonly TenantResolver $tenants,
        private readonly AuthorizationService $authz,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        $parameter = $request->route('organization');

        if ($parameter === null) {
            return $next($request);
        }

        $organizationId = $parameter instanceof Organization
            ? (int) $parameter->getKey()
            : (int) $parameter;

        $membership = $this->tenants->membershipFor($user);

        if ($membership === null) {
            abort(403, 'No organization selected. Call POST /api/v1/auth/switch-organization first.');
        }

        if ((int) $membership->organization_id === $organizationId) {
            return $next($request);
        }

        $own = $membership->organization;

        if ($own !== null
            && $own->isPlatform()
            && $this->authz->allows($user, 'platform.organization_access.manage', $own)) {
            return $next($request);
        }

        abort(403, 'This token is not bound to the requested organization.');
    }
}
