<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\InviteMemberRequest;
use App\Http\Resources\InvitationResource;
use App\Http\Resources\MembershipResource;
use App\Models\Organization;
use App\Services\Organization\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class InvitationController extends Controller
{
    public function __construct(
        private readonly MembershipService $membershipService,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('manageInvitations', $organization);

        $invitations = $this->membershipService->listPendingInvitations($organization);

        return InvitationResource::collection($invitations);
    }

    public function store(InviteMemberRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('manageInvitations', $organization);

        $invitation = $this->membershipService->invite(
            $organization,
            $request->user(),
            $request->validated()
        );

        return (new InvitationResource($invitation))
            ->response()
            ->setStatusCode(201);
    }

    public function resend(Request $request, Organization $organization, string $invitationId): JsonResponse
    {
        $this->authorize('manageInvitations', $organization);

        $this->membershipService->resendInvitation($organization, $request->user(), $invitationId);

        return new JsonResponse(['message' => 'Invitation resent successfully.']);
    }

    public function revoke(Request $request, Organization $organization, string $invitationId): JsonResponse
    {
        $this->authorize('manageInvitations', $organization);

        $this->membershipService->revokeInvitation($organization, $request->user(), $invitationId);

        return new JsonResponse(['message' => 'Invitation revoked successfully.']);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $membership = $this->membershipService->acceptInvitation(
            $token,
            $request->user()
        );

        return new JsonResponse([
            'message'    => 'Invitation accepted. You are now a member.',
            'membership' => new MembershipResource($membership->load('organization', 'role')),
        ]);
    }

    public function decline(Request $request, string $token): JsonResponse
    {
        $this->membershipService->declineInvitation($token, $request->user());

        return new JsonResponse(['message' => 'Invitation declined.']);
    }
}
