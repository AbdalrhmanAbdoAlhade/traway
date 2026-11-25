<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SwitchOrganizationRequest;
use App\Http\Resources\UserResource;
use App\Models\Membership;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated());

        return response()->json([
            'message' => 'User registered successfully. Please verify your email and phone.',
            'user'    => new UserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $token = $this->authService->login(
            $request->validated('email'),
            $request->validated('password'),
            $request->validated('device_name', 'api')
        );

        /** @var User $user */
        $user = $token->accessToken->tokenable;

        return response()->json([
            'message'          => 'Logged in successfully.',
            'token'            => $token->plainTextToken,
            'user'             => new UserResource($user),
            'organization_id'  => $token->accessToken->membership_id
                ? Membership::query()->whereKey($token->accessToken->membership_id)->value('organization_id')
                : null,
            'memberships'      => $this->membershipsPayload($user),
        ]);
    }

    public function switchOrganization(SwitchOrganizationRequest $request): JsonResponse
    {
        $token = $this->authService->switchOrganization(
            $request->user(),
            (int) $request->validated('organization_id')
        );

        return response()->json([
            'message'         => 'Organization switched successfully.',
            'token'           => $token->plainTextToken,
            'organization_id' => (int) $request->validated('organization_id'),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user'        => new UserResource($request->user()),
            'memberships' => $this->membershipsPayload($request->user()),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function membershipsPayload(User $user): array
    {
        return $this->authService->activeMemberships($user)
            ->map(fn (Membership $membership) => [
                'membership_id'     => $membership->getKey(),
                'organization_id'   => $membership->organization_id,
                'organization_name' => $membership->organization?->name,
                'organization_type' => $membership->organization?->type,
                'is_owner'          => (bool) $membership->is_owner,
            ])
            ->values()
            ->all();
    }
}
