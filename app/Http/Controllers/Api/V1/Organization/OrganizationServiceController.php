<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationService;
use App\Services\Organization\OrganizationServiceLifecycle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationServiceController extends Controller
{
    public function __construct(private readonly OrganizationServiceLifecycle $lifecycle) {}

    public function request(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'service' => ['required', 'string', 'in:hotels,tours,visas'],
        ]);

        $service = $this->lifecycle->request($request->user(), $organization, $data['service']);

        return response()->json(['data' => $service], 201);
    }

    public function approve(Request $request, OrganizationService $organizationService): JsonResponse
    {
        $service = $this->lifecycle->approve($request->user(), $organizationService);

        return response()->json(['data' => $service]);
    }

    public function reject(Request $request, OrganizationService $organizationService): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $service = $this->lifecycle->reject($request->user(), $organizationService, $data['reason']);

        return response()->json(['data' => $service]);
    }

    public function suspend(Request $request, OrganizationService $organizationService): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $service = $this->lifecycle->suspend($request->user(), $organizationService, $data['reason']);

        return response()->json(['data' => $service]);
    }

    public function reactivate(Request $request, OrganizationService $organizationService): JsonResponse
    {
        $service = $this->lifecycle->reactivate($request->user(), $organizationService);

        return response()->json(['data' => $service]);
    }

    public function disable(Request $request, OrganizationService $organizationService): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $service = $this->lifecycle->disable($request->user(), $organizationService, $data['reason']);

        return response()->json(['data' => $service]);
    }
}
