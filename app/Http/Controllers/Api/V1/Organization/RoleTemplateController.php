<?php

namespace App\Http\Controllers\Api\V1\Organization;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleTemplate\StoreRoleTemplateRequest;
use App\Http\Requests\RoleTemplate\UpdateRoleTemplateRequest;
use App\Http\Resources\RoleTemplateResource;
use App\Models\RoleTemplate;
use App\Services\Organization\RoleTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoleTemplateController extends Controller
{
    public function __construct(
        private readonly RoleTemplateService $roleTemplateService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $templates = $this->roleTemplateService->listForUser($request->user());

        return RoleTemplateResource::collection($templates);
    }

    public function store(StoreRoleTemplateRequest $request): JsonResponse
    {
        $template = $this->roleTemplateService->create(
            $request->user(),
            $request->validated()
        );

        return (new RoleTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function show(RoleTemplate $roleTemplate): RoleTemplateResource
    {
        $this->authorize('view', $roleTemplate);

        return new RoleTemplateResource($roleTemplate);
    }

    public function update(UpdateRoleTemplateRequest $request, RoleTemplate $roleTemplate): RoleTemplateResource
    {
        $this->authorize('update', $roleTemplate);

        $template = $this->roleTemplateService->update(
            $roleTemplate,
            $request->validated()
        );

        return new RoleTemplateResource($template);
    }

    public function destroy(RoleTemplate $roleTemplate): JsonResponse
    {
        $this->authorize('delete', $roleTemplate);

        $this->roleTemplateService->delete($roleTemplate);

        return new JsonResponse(['message' => 'Role template deleted successfully.']);
    }
}
