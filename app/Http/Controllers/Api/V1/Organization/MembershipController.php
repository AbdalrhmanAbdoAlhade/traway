<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\GrantPermissionRequest;
use App\Http\Requests\Membership\UpdateMemberRoleRequest;
use App\Http\Requests\Membership\UpdateMemberScopesRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Role;
use App\Services\Organization\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipService $membershipService,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $members = $this->membershipService->listMembers($request->user(), $organization);

        return MembershipResource::collection($members);
    }

    public function show(Request $request, Organization $organization, Membership $membership): MembershipResource
    {
        $membership = $this->scoped($organization, $membership);

        return new MembershipResource(
            $this->membershipService->viewMember($request->user(), $membership)
        );
    }

    public function updateRole(
        UpdateMemberRoleRequest $request,
        Organization $organization,
        Membership $membership,
    ): MembershipResource {
        $membership = $this->scoped($organization, $membership);

        $role = Role::query()->findOrFail($request->validated('role_id'));

        return new MembershipResource(
            $this->membershipService->changeRole($request->user(), $membership, $role)
        );
    }

    public function updateScopes(
        UpdateMemberScopesRequest $request,
        Organization $organization,
        Membership $membership,
    ): MembershipResource {
        $membership = $this->scoped($organization, $membership);

        return new MembershipResource(
            $this->membershipService->updateScopes(
                $request->user(),
                $membership,
                $request->validated('services'),
                $request->validated('hotel_ids'),
            )
        );
    }

    public function grantPermission(
        GrantPermissionRequest $request,
        Organization $organization,
        Membership $membership,
    ): JsonResponse {
        $membership = $this->scoped($organization, $membership);

        $this->membershipService->grantPermission(
            $request->user(),
            $membership,
            $request->validated('permission')
        );

        return response()->json(['message' => 'Permission granted successfully.']);
    }

    public function revokePermission(
        Request $request,
        Organization $organization,
        Membership $membership,
        string $permission,
    ): JsonResponse {
        $membership = $this->scoped($organization, $membership);

        $this->membershipService->revokePermission($request->user(), $membership, $permission);

        return response()->json(['message' => 'Permission revoked successfully.']);
    }

    public function suspend(Request $request, Organization $organization, Membership $membership): JsonResponse
    {
        $membership = $this->scoped($organization, $membership);

        $this->membershipService->suspend($request->user(), $membership);

        return response()->json(['message' => 'Member suspended successfully.']);
    }

    public function reinstate(Request $request, Organization $organization, Membership $membership): JsonResponse
    {
        $membership = $this->scoped($organization, $membership);

        $this->membershipService->reinstate($request->user(), $membership);

        return response()->json(['message' => 'Member reinstated successfully.']);
    }

    public function remove(Request $request, Organization $organization, Membership $membership): JsonResponse
    {
        $membership = $this->scoped($organization, $membership);

        $this->membershipService->remove($request->user(), $membership);

        return response()->json(['message' => 'Member removed successfully.']);
    }

    /**
     * A membership id from another organization, or one that is only an
     * invitation or already removed, does not exist for this tenant.
     */
    private function scoped(Organization $organization, Membership $membership): Membership
    {
        abort_unless(
            (int) $membership->organization_id === (int) $organization->getKey()
                && in_array($membership->status, [Membership::STATUS_ACTIVE, Membership::STATUS_SUSPENDED], true),
            404
        );

        return $membership;
    }
}
