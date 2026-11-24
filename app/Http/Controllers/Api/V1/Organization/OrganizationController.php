<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreOrganizationRequest;
use App\Http\Requests\Organization\TransferOwnershipRequest;
use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\Organization\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $organizations = $this->organizationService->listForUser(
            $request->user(),
            $request->only(['search', 'status', 'type'])
        );

        return OrganizationResource::collection($organizations);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->create(
            $request->user(),
            $request->validated()
        );

        return (new OrganizationResource($organization))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Organization $organization): OrganizationResource
    {
        $this->authorize('view', $organization);

        $organization->load(['owner']);

        return new OrganizationResource($organization);
    }

    public function update(UpdateOrganizationRequest $request, Organization $organization): OrganizationResource
    {
        $this->authorize('update', $organization);

        $organization = $this->organizationService->update(
            $organization,
            $request->validated()
        );

        return new OrganizationResource($organization);
    }

    public function destroy(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize('delete', $organization);

        $this->organizationService->delete($organization, $request->user());

        return response()->json(['message' => 'Organization deleted successfully.']);
    }

    public function transferOwnership(TransferOwnershipRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('transferOwnership', $organization);

        $this->organizationService->transferOwnership(
            $organization,
            $request->user(),
            $request->validated('new_owner_id')
        );

        return response()->json(['message' => 'Ownership transferred successfully.']);
    }
}
