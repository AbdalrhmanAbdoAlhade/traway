<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Models\Hotel;
use App\Models\Organization;
use App\Services\Hotel\HotelReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HotelReportController extends HotelPortalController
{
    public function __construct(private readonly HotelReportService $reports) {}

    public function overview(Request $request, Organization $organization): JsonResponse
    {
        $data = $request->validate([
            'date' => ['sometimes', 'date_format:Y-m-d'],
            'hotel_id' => ['sometimes', 'integer'],
        ]);

        $hotelId = $data['hotel_id'] ?? null;

        if ($hotelId !== null) {
            $hotel = Hotel::query()->forOrganization($organization)->findOrFail($hotelId);
            $this->authorize('viewReports', $hotel);
        } else {
            $this->authorize('viewReportsAny', Hotel::class);
        }

        return new JsonResponse($this->reports->overview(
            $organization,
            $this->membership($request),
            $hotelId,
            isset($data['date']) ? CarbonImmutable::parse($data['date']) : CarbonImmutable::today(),
        ));
    }
}
