<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
use App\Models\Organization;
use App\Models\Role;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationRoleController extends Controller
{
    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly TenantResolver $tenants,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $membership = $this->tenants->membershipFor($request->user());

        abort_unless(
            $membership !== null
                && (int) $membership->organization_id === (int) $organization->getKey()
                && $this->authz->allows($request->user(), 'roles.view', $organization),
            403
        );

        $ownerTemplates = array_keys(config('authorization.protected_owner_templates', []));

        $roles = Role::query()
            ->with('permissions')
            ->where('organization_id', $organization->getKey())
            ->where('family', Role::FAMILY_ORGANIZATION)
            ->where('is_template', false)
            ->whereNotIn('name', $ownerTemplates)
            ->orderBy('name')
            ->get();

        return RoleResource::collection($roles);
    }
}
