<?php

namespace App\Services\Hotel;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\HotelBooking;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RoomTypeService
{
    /** A new room type is immediately sellable for this many days. */
    private const CALENDAR_DAYS = 365;

    public function __construct(private readonly RoomRateService $rates) {}

    /**
     * @return Collection<int, RoomType>
     */
    public function list(Hotel $hotel): Collection
    {
        return $hotel->roomTypes()->orderBy('id')->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Hotel $hotel, User $actor, array $data): RoomType
    {
        $merged = array_merge([
            'max_children' => 0,
            'cancellation_policy' => RoomType::CANCELLATION_NON_REFUNDABLE,
        ], $data);

        $data = $this->validated($merged, $data);

        return DB::transaction(function () use ($hotel, $actor, $data) {
            $roomType = $hotel->roomTypes()->create($data);

            if ($roomType->is_active) {
                $this->seedCalendar($roomType, $actor);
            }

            return $roomType->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(RoomType $roomType, array $data): RoomType
    {
        $merged = array_merge($roomType->only([
            'max_adults', 'max_children', 'max_occupancy',
            'cancellation_policy', 'free_cancellation_hours_before',
        ]), $data);

        $data = $this->validated($merged, $data);

        return DB::transaction(function () use ($roomType, $data) {
            if (array_key_exists('total_units', $data)
                && (int) $data['total_units'] !== (int) $roomType->total_units) {
                $this->applyUnitChange($roomType, (int) $data['total_units']);
            }

            $roomType->fill($data)->save();

            return $roomType->refresh();
        });
    }

    public function delete(RoomType $roomType): void
    {
        $hasUpcoming = HotelBooking::query()
            ->where('room_type_id', $roomType->getKey())
            ->where('check_out', '>', today()->toDateString())
            ->whereHas('booking', fn ($query) => $query->whereIn('status', [
                Booking::STATUS_PENDING_PAYMENT,
                Booking::STATUS_CONFIRMED,
            ]))
            ->exists();

        if ($hasUpcoming) {
            throw new BusinessRuleException('This room type has upcoming bookings. Deactivate it instead of deleting it.');
        }

        DB::transaction(function () use ($roomType) {
            $roomType->rates()->where('date', '>=', today()->toDateString())->delete();
            $roomType->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $merged
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validated(array $merged, array $data): array
    {
        $adults = (int) $merged['max_adults'];
        $children = (int) ($merged['max_children'] ?? 0);
        $occupancy = (int) $merged['max_occupancy'];

        if ($occupancy < $adults) {
            throw new BusinessRuleException('Maximum occupancy cannot be lower than maximum adults.');
        }

        if ($occupancy > $adults + $children) {
            throw new BusinessRuleException('Maximum occupancy cannot exceed adults plus children.');
        }

        if ($merged['cancellation_policy'] === RoomType::CANCELLATION_FREE_UNTIL_DEADLINE) {
            if ((int) ($merged['free_cancellation_hours_before'] ?? 0) < 1) {
                throw new BusinessRuleException('Free cancellation needs a deadline in hours before check-in.');
            }
        } else {
            $data['free_cancellation_hours_before'] = null;
        }

        return $data;
    }

    private function applyUnitChange(RoomType $roomType, int $newTotal): void
    {
        $today = CarbonImmutable::today();

        $booked = $this->rates->bookedUnitsByDate($roomType, $today, $today->addDays(self::CALENDAR_DAYS));
        $peak = $booked === [] ? 0 : max($booked);

        if ($newTotal < $peak) {
            throw new BusinessRuleException(
                "Cannot go below {$peak} units: that many rooms are already booked on a future night."
            );
        }

        $delta = $newTotal - (int) $roomType->total_units;

        RoomRate::query()
            ->where('room_type_id', $roomType->getKey())
            ->where('date', '>=', $today->toDateString())
            ->update([
                'available_units' => DB::raw('GREATEST(0, CAST(available_units AS SIGNED) + '.$delta.')'),
            ]);
    }

    private function seedCalendar(RoomType $roomType, User $actor): void
    {
        $start = CarbonImmutable::today();
        $rows = [];

        for ($i = 0; $i < self::CALENDAR_DAYS; $i++) {
            $rows[] = [
                'room_type_id' => $roomType->getKey(),
                'date' => $start->addDays($i)->toDateString(),
                'price_cents' => $roomType->base_price_cents,
                'available_units' => $roomType->total_units,
                'is_stop_sell' => false,
                'updated_by' => $actor->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            RoomRate::insert($chunk);
        }
    }
}
