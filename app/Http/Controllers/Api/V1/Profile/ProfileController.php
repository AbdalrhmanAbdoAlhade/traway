<?php

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileService $profileService,
    ) {}

    public function show(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->load('memberships.organization')
        );
    }

    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $this->profileService->update($request->user(), $request->validated());

        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->profileService->updatePassword(
            $request->user(),
            $request->validated('current_password'),
            $request->validated('password')
        );

        return new JsonResponse(['message' => 'Password updated. Please log in again.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        $this->profileService->deleteAccount($request->user(), $request->validated('password'));

        return new JsonResponse(['message' => 'Account deleted successfully.']);
    }
}
