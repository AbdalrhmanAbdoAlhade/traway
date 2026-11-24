<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\UpdateMemberRoleRequest;
use App\Http\Requests\Membership\UpdateMemberScopesRequest;
use App\Http\Resources\MembershipResource;
use App\Models\Membership;
use App\Models\Organization;
use App\Services\Organization\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MembershipController extends Controller
{
    public function __construct(
        private readonly MembershipService $membershipService,
    ) {}

    public function index(Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewMembers', $organization);

        $members = $this->membershipService->listMembers($organization);

        return MembershipResource::collection($members);
    }

    public function updateRole(
        UpdateMemberRoleRequest $request,
        Organization $organization,
        Membership $membership,
    ): MembershipResource {
        $this->authorize('updateMemberRole', [$organization, $membership]);

        $membership = $this->membershipService->updateRole(
            $organization,
            $membership,
            $request->user(),
            $request->validated('role_id')
        );

        return new MembershipResource($membership->load('user', 'role'));
    }

    public function updateScopes(
        UpdateMemberScopesRequest $request,
        Organization $organization,
        Membership $membership,
    ): MembershipResource {
        $this->authorize('updateMemberScopes', [$organization, $membership]);

        $membership = $this->membershipService->updateScopes(
            $organization,
            $membership,
            $request->user(),
            $request->validated('scopes', [])
        );

        return new MembershipResource($membership->load('user', 'role'));
    }

    public function suspend(Request $request, Organization $organization, Membership $membership): JsonResponse
    {
        $this->authorize('suspendMember', [$organization, $membership]);

        $this->membershipService->suspend($organization, $membership, $request->user());

        return response()->json(['message' => 'Member suspended successfully.']);
    }

    public function remove(Request $request, Organization $organization, Membership $membership): JsonResponse
    {
        $this->authorize('removeMember', [$organization, $membership]);

        $this->membershipService->remove($organization, $membership, $request->user());

        return response()->json(['message' => 'Member removed successfully.']);
    }
}
