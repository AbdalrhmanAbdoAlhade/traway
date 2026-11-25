<?php

namespace App\Services\Hotel;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Services\Authorization\AuthorizationService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Hotel overview (D-05.1): KPIs for one night, availability by room type,
 * 14-night occupancy/revenue series and the arrivals list.
 */
class HotelReportService
{
    private const SERIES_DAYS = 14;

    private const ARRIVALS_LIMIT = 20;

    public function __construct(private readonly AuthorizationService $authz) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(Organization $organization, Membership $actor, ?int $hotelId, CarbonImmutable $date): array
    {
        $hotelIds = $this->authz->restrictToHotels(
            Hotel::query()
                ->forOrganization($organization)
                ->when($hotelId, fn ($query) => $query->where('id', $hotelId)),
            $actor,
            'id'
        )->pluck('id')->map(fn ($id) => (int) $id)->all();

        $to = $date->addDays(self::SERIES_DAYS - 1);

        if ($hotelIds === []) {
            return $this->empty($date, $to);
        }

        $totalUnits = (int) RoomType::query()
            ->whereIn('hotel_id', $hotelIds)
            ->where('is_active', true)
            ->sum('total_units');

        $series = $this->series($organization, $hotelIds, $date, $to, $totalUnits);

        $arrivals = Booking::query()
            ->forOrganization($organization)
            ->where('service', Booking::SERVICE_HOTEL)
            ->where('status', Booking::STATUS_CONFIRMED)
            ->whereIn('hotel_id', $hotelIds)
            ->whereHas('hotel', fn ($h) => $h->where('check_in', $date->toDateString()));

        $arrivalsCount = (clone $arrivals)->count();

        $arrivalRows = $arrivals->with('hotel.roomType')
            ->orderBy('id')
            ->limit(self::ARRIVALS_LIMIT)
            ->get()
            ->map(fn (Booking $booking) => [
                'booking_id' => $booking->id,
                'reference' => $booking->reference,
                'guest_name' => $booking->hotel?->guest_name,
                'room_type' => $booking->hotel?->roomType?->name,
                'nights' => $booking->hotel?->nights,
                'rooms' => $booking->hotel?->rooms,
                'checked_in_at' => $booking->hotel?->checked_in_at?->toISOString(),
            ])->all();

        $first = $series[0];

        return [
            'date' => $date->toDateString(),
            'hotel_ids' => $hotelIds,
            'kpis' => [
                'total_units' => $totalUnits,
                'occupied_rooms' => $first['occupied_rooms'],
                'occupancy_pct' => $first['occupancy_pct'],
                'nightly_revenue_cents' => $first['revenue_cents'],
                'adr_cents' => $first['adr_cents'],
                'arrivals_today' => $arrivalsCount,
            ],
            'availability' => $this->availability($hotelIds, $date),
            'occupancy_series' => $series,
            'arrivals' => $arrivalRows,
        ];
    }

    /**
     * @param  array<int, int>  $hotelIds
     * @return array<int, array<string, mixed>>
     */
    private function series(Organization $organization, array $hotelIds, CarbonImmutable $from, CarbonImmutable $to, int $totalUnits): array
    {
        $stays = DB::table('hotel_bookings as hb')
            ->join('bookings as b', 'b.id', '=', 'hb.booking_id')
            ->where('b.organization_id', $organization->getKey())
            ->whereIn('hb.hotel_id', $hotelIds)
            ->whereIn('b.status', [Booking::STATUS_CONFIRMED, Booking::STATUS_COMPLETED])
            ->where('hb.check_in', '<=', $to->toDateString())
            ->where('hb.check_out', '>', $from->toDateString())
            ->get(['hb.check_in', 'hb.check_out', 'hb.nights', 'hb.rooms', 'b.subtotal_cents']);

        $occupied = [];
        $revenue = [];

        foreach ($stays as $stay) {
            $start = CarbonImmutable::parse($stay->check_in);
            $end = CarbonImmutable::parse($stay->check_out)->subDay();
            $perNight = intdiv((int) $stay->subtotal_cents, max(1, (int) $stay->nights));

            if ($start->lt($from)) {
                $start = $from;
            }

            if ($end->gt($to)) {
                $end = $to;
            }

            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $key = $day->toDateString();
                $occupied[$key] = ($occupied[$key] ?? 0) + (int) $stay->rooms;
                $revenue[$key] = ($revenue[$key] ?? 0) + $perNight;
            }
        }

        $series = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $rooms = $occupied[$key] ?? 0;
            $sum = $revenue[$key] ?? 0;

            $series[] = [
                'date' => $key,
                'occupied_rooms' => $rooms,
                'occupancy_pct' => $totalUnits > 0 ? round($rooms / $totalUnits * 100, 1) : 0.0,
                'revenue_cents' => $sum,
                'adr_cents' => $rooms > 0 ? intdiv($sum, $rooms) : 0,
            ];
        }

        return $series;
    }

    /**
     * @param  array<int, int>  $hotelIds
     * @return array<int, array<string, mixed>>
     */
    private function availability(array $hotelIds, CarbonImmutable $date): array
    {
        $types = RoomType::query()
            ->whereIn('hotel_id', $hotelIds)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();

        $rates = RoomRate::query()
            ->whereIn('room_type_id', $types->pluck('id'))
            ->where('date', $date->toDateString())
            ->get()
            ->keyBy('room_type_id');

        return $types->map(function (RoomType $type) use ($rates) {
            $rate = $rates->get($type->id);

            return [
                'room_type_id' => $type->id,
                'hotel_id' => $type->hotel_id,
                'name' => $type->name,
                'total_units' => (int) $type->total_units,
                'available_units' => (int) ($rate?->available_units ?? 0),
                'price_cents' => (int) ($rate?->price_cents ?? $type->base_price_cents),
                'is_stop_sell' => (bool) ($rate?->is_stop_sell ?? false),
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function empty(CarbonImmutable $date, CarbonImmutable $to): array
    {
        $series = [];

        for ($day = $date; $day->lte($to); $day = $day->addDay()) {
            $series[] = [
                'date' => $day->toDateString(),
                'occupied_rooms' => 0,
                'occupancy_pct' => 0.0,
                'revenue_cents' => 0,
                'adr_cents' => 0,
            ];
        }

        return [
            'date' => $date->toDateString(),
            'hotel_ids' => [],
            'kpis' => [
                'total_units' => 0,
                'occupied_rooms' => 0,
                'occupancy_pct' => 0.0,
                'nightly_revenue_cents' => 0,
                'adr_cents' => 0,
                'arrivals_today' => 0,
            ],
            'availability' => [],
            'occupancy_series' => $series,
            'arrivals' => [],
        ];
    }
}
