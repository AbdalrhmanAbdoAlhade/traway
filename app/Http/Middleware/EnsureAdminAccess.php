<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use App\Services\Authorization\AuthorizationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Platform-staff gate for platform endpoints.
 *
 * There is no global bypass: the user must hold an ACTIVE membership of the
 * platform organization and, when a permission is passed to the middleware
 * (`platform.staff:platform.finance.view`), that permission is evaluated inside
 * the platform tenant.
 */
class EnsureAdminAccess
{
    public function __construct(private readonly AuthorizationService $authz) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401, 'Unauthenticated.');
        }

        if (! $user->isActive() || ! $user->isPlatformStaff()) {
            abort(403, 'Platform access is required.');
        }

        if ($permission !== null) {
            $platform = Organization::query()
                ->where('type', Organization::TYPE_PLATFORM)
                ->where('status', Organization::STATUS_ACTIVE)
                ->first();

            if ($platform === null || ! $this->authz->allows($user, $permission, $platform)) {
                abort(403, 'This action is unauthorized.');
            }
        }

        return $next($request);
    }
}
