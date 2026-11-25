<?php

namespace App\Services\Hotel;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * The per-day price/availability calendar (BR-061, BR-066, BR-106).
 * available_units is the REMAINING sellable units; stop-sell only blocks new
 * bookings and never touches existing ones.
 */
class RoomRateService
{
    public const MAX_VIEW_DAYS = 93;

    public const MAX_EDIT_DAYS = 366;

    private const MIN_PRICE_CENTS = 100;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function calendar(RoomType $roomType, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $this->assertRange($from, $to, self::MAX_VIEW_DAYS);

        $rates = $roomType->rates()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (RoomRate $rate) => $rate->date->toDateString());

        $booked = $this->bookedUnitsByDate($roomType, $from, $to);
        $total = (int) $roomType->total_units;
        $days = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            $key = $day->toDateString();
            $rate = $rates->get($key);
            $available = $rate?->available_units ?? 0;
            $stopSell = (bool) ($rate?->is_stop_sell ?? false);

            $days[] = [
                'date' => $key,
                'is_configured' => $rate !== null,
                'price_cents' => $rate?->price_cents ?? (int) $roomType->base_price_cents,
                'available_units' => (int) $available,
                'total_units' => $total,
                'booked_units' => $booked[$key] ?? 0,
                'is_stop_sell' => $stopSell,
                'is_almost_full' => $available > 0 && $available <= max(1, (int) ceil($total * 0.2)),
                'is_sellable' => $rate !== null && ! $stopSell && $available > 0,
            ];
        }

        return $days;
    }

    /**
     * @param  array{from:string,to:string,weekdays?:array<int,int>,price_cents?:int,price_adjust_cents?:int,available_units?:int,is_stop_sell?:bool}  $data
     * @return array{room_type_id:int,days_updated:int}
     */
    public function bulkUpdate(RoomType $roomType, User $actor, array $data): array
    {
        $from = CarbonImmutable::parse($data['from'])->startOfDay();
        $to = CarbonImmutable::parse($data['to'])->startOfDay();

        $this->assertRange($from, $to, self::MAX_EDIT_DAYS);

        if ($from->lt(CarbonImmutable::today())) {
            throw new BusinessRuleException('Past dates cannot be changed.');
        }

        if (! $roomType->is_active) {
            throw new BusinessRuleException('Activate the room type before changing its calendar.');
        }

        if (array_key_exists('price_cents', $data) && array_key_exists('price_adjust_cents', $data)) {
            throw new BusinessRuleException('Set a price or adjust it, not both.');
        }

        $weekdays = array_map('intval', $data['weekdays'] ?? []);

        $existing = $roomType->rates()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->keyBy(fn (RoomRate $rate) => $rate->date->toDateString());

        $booked = $this->bookedUnitsByDate($roomType, $from, $to);
        $total = (int) $roomType->total_units;
        $rows = [];

        for ($day = $from; $day->lte($to); $day = $day->addDay()) {
            if ($weekdays !== [] && ! in_array($day->dayOfWeek, $weekdays, true)) {
                continue;
            }

            $key = $day->toDateString();
            $current = $existing->get($key);
            $maxAvailable = max(0, $total - ($booked[$key] ?? 0));

            $price = $current?->price_cents ?? (int) $roomType->base_price_cents;

            if (array_key_exists('price_cents', $data)) {
                $price = (int) $data['price_cents'];
            } elseif (array_key_exists('price_adjust_cents', $data)) {
                $price += (int) $data['price_adjust_cents'];
            }

            if ($price < self::MIN_PRICE_CENTS) {
                throw new BusinessRuleException("The price for {$key} would fall below the minimum.");
            }

            $available = $current?->available_units ?? $maxAvailable;

            if (array_key_exists('available_units', $data)) {
                $available = (int) $data['available_units'];

                if ($available > $maxAvailable) {
                    throw new BusinessRuleException(
                        "Only {$maxAvailable} units can be offered on {$key}: the rest are already booked."
                    );
                }
            }

            $stopSell = array_key_exists('is_stop_sell', $data)
                ? (bool) $data['is_stop_sell']
                : (bool) ($current?->is_stop_sell ?? false);

            $rows[] = [
                'room_type_id' => $roomType->getKey(),
                'date' => $key,
                'price_cents' => $price,
                'available_units' => $available,
                'is_stop_sell' => $stopSell,
                'updated_by' => $actor->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows === []) {
            throw new BusinessRuleException('No dates match the selected weekdays.');
        }

        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                RoomRate::upsert(
                    $chunk,
                    ['room_type_id', 'date'],
                    ['price_cents', 'available_units', 'is_stop_sell', 'updated_by', 'updated_at']
                );
            }
        });

        return ['room_type_id' => (int) $roomType->getKey(), 'days_updated' => count($rows)];
    }

    /**
     * Rooms held per night by pending (not expired) and confirmed bookings.
     *
     * @return array<string, int>
     */
    public function bookedUnitsByDate(RoomType $roomType, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $stays = DB::table('hotel_bookings as hb')
            ->join('bookings as b', 'b.id', '=', 'hb.booking_id')
            ->where('hb.room_type_id', $roomType->getKey())
            ->where('hb.check_in', '<=', $to->toDateString())
            ->where('hb.check_out', '>', $from->toDateString())
            ->where(function ($query) {
                $query->where('b.status', Booking::STATUS_CONFIRMED)
                    ->orWhere(function ($pending) {
                        $pending->where('b.status', Booking::STATUS_PENDING_PAYMENT)
                            ->where('b.expires_at', '>', now());
                    });
            })
            ->get(['hb.check_in', 'hb.check_out', 'hb.rooms']);

        $booked = [];

        foreach ($stays as $stay) {
            $start = CarbonImmutable::parse($stay->check_in);
            $end = CarbonImmutable::parse($stay->check_out)->subDay();

            if ($start->lt($from)) {
                $start = $from;
            }

            if ($end->gt($to)) {
                $end = $to;
            }

            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                $key = $day->toDateString();
                $booked[$key] = ($booked[$key] ?? 0) + (int) $stay->rooms;
            }
        }

        return $booked;
    }

    private function assertRange(CarbonImmutable $from, CarbonImmutable $to, int $maxDays): void
    {
        if ($to->lt($from)) {
            throw new BusinessRuleException('The end date is before the start date.');
        }

        if ((int) $from->diffInDays($to) + 1 > $maxDays) {
            throw new BusinessRuleException("The date range cannot exceed {$maxDays} days.");
        }
    }
}
