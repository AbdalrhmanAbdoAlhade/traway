<?php

namespace App\Http\Controllers\Api\V1\Hotel;

use App\Http\Resources\HotelBookingResource;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Organization;
use App\Services\Authorization\AuthorizationService;
use App\Services\Hotel\HotelBookingService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HotelBookingController extends HotelPortalController
{
    public function __construct(
        private readonly HotelBookingService $bookings,
        private readonly AuthorizationService $authz,
    ) {}

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Booking::class);

        $filters = $this->filters($request, $organization);
        $membership = $this->membership($request);

        $this->exposeContact($request, $membership->organization_id);

        return HotelBookingResource::collection(
            $this->bookings->paginate($organization, $membership, $filters, (int) $request->integer('per_page', 20))
        )->additional(['tabs' => $this->bookings->tabCounts($organization, $membership, $filters)]);
    }

    public function show(Request $request, Organization $organization, int $booking): HotelBookingResource
    {
        $membership = $this->membership($request);
        $found = $this->bookings->find($organization, $membership, $booking);

        $this->authorize('view', $found);
        $this->exposeContact($request, $membership->organization_id);

        return new HotelBookingResource($found);
    }

    public function checkIn(Request $request, Organization $organization, int $booking): HotelBookingResource
    {
        $data = $request->validate(['room_number' => ['nullable', 'string', 'max:20']]);

        $membership = $this->membership($request);
        $found = $this->bookings->find($organization, $membership, $booking);

        $this->authorize('checkIn', $found);
        $this->exposeContact($request, $membership->organization_id);

        return new HotelBookingResource(
            $this->bookings->checkIn($found, $membership, $data['room_number'] ?? null)
        );
    }

    public function checkOut(Request $request, Organization $organization, int $booking): HotelBookingResource
    {
        $membership = $this->membership($request);
        $found = $this->bookings->find($organization, $membership, $booking);

        $this->authorize('checkOut', $found);
        $this->exposeContact($request, $membership->organization_id);

        return new HotelBookingResource($this->bookings->checkOut($found, $request->user(), $membership));
    }

    public function export(Request $request, Organization $organization): StreamedResponse
    {
        $this->authorize('export', Booking::class);

        $filters = $this->filters($request, $organization);
        $membership = $this->membership($request);
        $withContact = $this->authz->allows($request->user(), 'customers.view', $membership->organization_id);

        $columns = $this->bookings->exportColumns($withContact);
        $rows = $this->bookings->exportRows($organization, $membership, $filters, $withContact);

        return response()->streamDownload(function () use ($columns, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $columns);

            foreach ($rows as $row) {
                fputcsv($out, array_map([$this, 'csvSafe'], $row));
            }

            fclose($out);
        }, 'hotel-bookings-'.now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request, Organization $organization): array
    {
        $filters = $request->validate([
            'tab' => ['sometimes', 'in:'.implode(',', HotelBookingService::TABS)],
            'hotel_id' => ['sometimes', 'integer'],
            'q' => ['sometimes', 'string', 'max:100'],
            'from' => ['sometimes', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'date_format:Y-m-d'],
        ]);

        if (isset($filters['hotel_id'])) {
            Hotel::query()->forOrganization($organization)->findOrFail($filters['hotel_id']);
        }

        return $filters;
    }

    /**
     * Guest contact is visible only with customers.view (DEC-09).
     */
    private function exposeContact(Request $request, int $organizationId): void
    {
        $request->attributes->set(
            'show_contact',
            $this->authz->allows($request->user(), 'customers.view', $organizationId)
        );
    }

    private function csvSafe(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }
}
