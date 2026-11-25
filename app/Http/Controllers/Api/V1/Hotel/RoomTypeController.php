<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Http\Requests\Hotel\SaveRoomTypeRequest;
use App\Http\Resources\RoomTypeResource;
use App\Models\Hotel;
use App\Models\Organization;
use App\Models\RoomType;
use App\Services\Hotel\RoomTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RoomTypeController extends HotelPortalController
{
    public function __construct(private readonly RoomTypeService $roomTypes) {}

    public function index(Organization $organization, Hotel $hotel): AnonymousResourceCollection
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('viewForHotel', [RoomType::class, $hotel]);

        return RoomTypeResource::collection($this->roomTypes->list($hotel));
    }

    public function store(SaveRoomTypeRequest $request, Organization $organization, Hotel $hotel): JsonResponse
    {
        $this->hotelIn($organization, $hotel);
        $this->authorize('createFor', [RoomType::class, $hotel]);

        $roomType = $this->roomTypes->create($hotel, $request->user(), $request->validated());

        return (new RoomTypeResource($roomType))->response()->setStatusCode(201);
    }

    public function show(Organization $organization, Hotel $hotel, RoomType $roomType): RoomTypeResource
    {
        $this->roomTypeIn($organization, $hotel, $roomType);
        $this->authorize('view', $roomType);

        return new RoomTypeResource($roomType);
    }

    public function update(
        SaveRoomTypeRequest $request,
        Organization $organization,
        Hotel $hotel,
        RoomType $roomType,
    ): RoomTypeResource {
        $this->roomTypeIn($organization, $hotel, $roomType);
        $this->authorize('update', $roomType);

        return new RoomTypeResource($this->roomTypes->update($roomType, $request->validated()));
    }

    public function destroy(Request $request, Organization $organization, Hotel $hotel, RoomType $roomType): JsonResponse
    {
        $this->roomTypeIn($organization, $hotel, $roomType);
        $this->authorize('delete', $roomType);

        $this->roomTypes->delete($roomType);

        return new JsonResponse(['message' => 'Room type deleted successfully.']);
    }
}
