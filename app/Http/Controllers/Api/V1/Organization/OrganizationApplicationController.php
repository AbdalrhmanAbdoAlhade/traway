<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\OrganizationApplication\ReviewApplicationRequest;
use App\Http\Requests\OrganizationApplication\StoreOrganizationApplicationRequest;
use App\Http\Resources\OrganizationApplicationResource;
use App\Models\OrganizationApplication;
use App\Services\Organization\OrganizationApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class OrganizationApplicationController extends Controller
{
    public function __construct(
        private readonly OrganizationApplicationService $applicationService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', OrganizationApplication::class);

        $applications = $this->applicationService->list(
            $request->user(),
            $request->query('status')
        );

        return OrganizationApplicationResource::collection($applications);
    }

    public function store(StoreOrganizationApplicationRequest $request): JsonResponse
    {
        $application = $this->applicationService->submit(
            $request->user(),
            $request->validated()
        );

        return (new OrganizationApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    public function show(OrganizationApplication $application): OrganizationApplicationResource
    {
        $this->authorize('view', $application);

        return new OrganizationApplicationResource($application->load('applicant', 'reviewer'));
    }

    public function approve(ReviewApplicationRequest $request, OrganizationApplication $application): JsonResponse
    {
        $this->authorize('review', $application);

        $this->applicationService->approve($application, $request->user());

        return new JsonResponse(['message' => 'Application approved successfully.']);
    }

    public function reject(ReviewApplicationRequest $request, OrganizationApplication $application): JsonResponse
    {
        $this->authorize('review', $application);

        $this->applicationService->reject(
            $application,
            $request->user(),
            $request->validated('rejection_reason')
        );

        return new JsonResponse(['message' => 'Application rejected.']);
    }
}
