<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membership\InviteMemberRequest;
use App\Http\Resources\InvitationResource;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Organization\InvitationResult;
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
        $invitations = $this->membershipService->listPendingInvitations($request->user(), $organization);

        return InvitationResource::collection($invitations);
    }

    public function store(InviteMemberRequest $request, Organization $organization): JsonResponse
    {
        $invitee = User::query()->where('email', $request->validated('email'))->first();

        if ($invitee === null) {
            throw new BusinessRuleException('No account exists for this email. Ask the person to register first.');
        }

        $roleId = $request->validated('role_id');
        $role = $roleId !== null ? Role::query()->findOrFail($roleId) : null;

        $result = $this->membershipService->invite(
            $request->user(),
            $organization,
            $invitee,
            $role,
            $request->validated('services', []),
            $request->validated('hotel_ids', []),
            $request->validated('job_title'),
        );

        return $this->invitationResponse($result, 201);
    }

    public function resend(Request $request, Organization $organization, int $invitationId): JsonResponse
    {
        $result = $this->membershipService->resendInvitation($request->user(), $organization, $invitationId);

        return $this->invitationResponse($result, 200);
    }

    public function revoke(Request $request, Organization $organization, int $invitationId): JsonResponse
    {
        $this->membershipService->revokeInvitation($request->user(), $organization, $invitationId);

        return new JsonResponse(['message' => 'Invitation revoked successfully.']);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $membership = $this->membershipService
            ->acceptInvitation($request->user(), $token)
            ->load('organization');

        return new JsonResponse([
            'message' => 'Invitation accepted. You are now a member.',
            'membership' => [
                'id' => $membership->id,
                'status' => $membership->status,
                'organization' => [
                    'id' => $membership->organization->id,
                    'name' => $membership->organization->name,
                    'type' => $membership->organization->type,
                ],
            ],
        ]);
    }

    public function decline(Request $request, string $token): JsonResponse
    {
        $this->membershipService->declineInvitation($request->user(), $token);

        return new JsonResponse(['message' => 'Invitation declined.']);
    }

    /**
     * The plain token exists only in this response. Until the mail notification
     * is built it is exposed outside production so the flow can be tested end to end.
     */
    private function invitationResponse(InvitationResult $result, int $status): JsonResponse
    {
        $result->membership->loadMissing(['user', 'inviter', 'serviceScopes', 'hotelScopes']);

        $body = ['data' => (new InvitationResource($result->membership))->resolve()];

        if (! app()->isProduction()) {
            $body['meta'] = ['plain_token' => $result->plainToken];
        }

        return new JsonResponse($body, $status);
    }
}
