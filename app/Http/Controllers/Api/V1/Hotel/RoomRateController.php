<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Http\Requests\Hotel\BulkRateRequest;
use App\Models\Hotel;
use App\Models\Organization;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Services\Hotel\RoomRateService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomRateController extends HotelPortalController
{
    public function __construct(private readonly RoomRateService $rates) {}

    public function calendar(Request $request, Organization $organization, Hotel $hotel, RoomType $roomType): JsonResponse
    {
        $this->roomTypeIn($organization, $hotel, $roomType);
        $this->authorize('viewCalendar', [RoomRate::class, $roomType]);

        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        return new JsonResponse([
            'room_type_id' => $roomType->id,
            'days' => $this->rates->calendar(
                $roomType,
                CarbonImmutable::parse($data['from']),
                CarbonImmutable::parse($data['to']),
            ),
        ]);
    }

    /**
     * Prices and availability need rates.update; stop-sell needs rates.stop_sell.
     * A request that touches both needs both.
     */
    public function bulkUpdate(BulkRateRequest $request, Organization $organization, Hotel $hotel, RoomType $roomType): JsonResponse
    {
        $this->roomTypeIn($organization, $hotel, $roomType);

        if ($request->hasAny(['price_cents', 'price_adjust_cents', 'available_units'])) {
            $this->authorize('updateCalendar', [RoomRate::class, $roomType]);
        }

        if ($request->has('is_stop_sell')) {
            $this->authorize('stopSellCalendar', [RoomRate::class, $roomType]);
        }

        return new JsonResponse($this->rates->bulkUpdate($roomType, $request->user(), $request->validated()));
    }
}
