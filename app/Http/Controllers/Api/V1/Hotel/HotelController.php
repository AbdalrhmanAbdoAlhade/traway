<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Http\Requests\Hotel\SaveHotelRequest;
use App\Http\Resources\HotelResource;
use App\Models\Hotel;
use App\Models\Organization;
use App\Services\Hotel\HotelProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HotelController extends HotelPortalController
{
    public function __construct(private readonly HotelProfileService $hotels) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Hotel::class);

        return HotelResource::collection(
            $this->hotels->listFor($organization, $this->membership($request))
        );
    }

    public function store(SaveHotelRequest $request, Organization $organization): JsonResponse
    {
        $this->authorize('create', Hotel::class);

        $hotel = $this->hotels->create($organization, $this->membership($request), $request->validated());

        return (new HotelResource($hotel))->response()->setStatusCode(201);
    }

    public function show(Organization $organization, Hotel $hotel): HotelResource
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('view', $hotel);

        return new HotelResource($hotel);
    }

    public function update(SaveHotelRequest $request, Organization $organization, Hotel $hotel): HotelResource
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('update', $hotel);

        return new HotelResource($this->hotels->update($hotel, $request->validated()));
    }

    public function readiness(Organization $organization, Hotel $hotel): JsonResponse
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('update', $hotel);

        $missing = $this->hotels->missingForPublishing($hotel);

        return new JsonResponse(['ready' => $missing === [], 'missing' => $missing]);
    }

    public function publish(Organization $organization, Hotel $hotel): HotelResource
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('publish', $hotel);

        return new HotelResource($this->hotels->publish($hotel));
    }

    public function unpublish(Organization $organization, Hotel $hotel): HotelResource
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('publish', $hotel);

        return new HotelResource($this->hotels->unpublish($hotel));
    }
}
