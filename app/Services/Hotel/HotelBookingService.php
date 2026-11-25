<?php

namespace App\Services\Hotel;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\HotelBooking;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class HotelBookingService
{
    public const TABS = ['all', 'arriving_today', 'in_house', 'upcoming', 'cancelled'];

    public function __construct(private readonly AuthorizationService $authz) {}

    /**
     * Tenant + property scope + assignment are all applied in the query (BR-115),
     * so an id outside the caller's reach simply does not exist.
     *
     * @param  array{tab?:string,hotel_id?:int,q?:string,from?:string,to?:string}  $filters
     */
    public function query(Organization $organization, Membership $actor, array $filters = []): Builder
    {
        $query = Booking::query()
            ->forOrganization($organization)
            ->where('service', Booking::SERVICE_HOTEL)
            // A reservation reaches the portal only once confirmed (BR-068).
            ->whereNotIn('status', [Booking::STATUS_PENDING_PAYMENT, Booking::STATUS_EXPIRED])
            ->with(['hotel.roomType', 'hotelProperty:id,name,city']);

        $this->authz->restrictToHotels($query, $actor, 'hotel_id');
        $this->authz->restrictToAssigned($query, $actor);

        if (! empty($filters['hotel_id'])) {
            $query->where('hotel_id', (int) $filters['hotel_id']);
        }

        if (! empty($filters['q'])) {
            $term = '%'.addcslashes((string) $filters['q'], '%_\\').'%';

            $query->where(function (Builder $w) use ($term) {
                $w->where('reference', 'like', $term)
                    ->orWhere('contact_name', 'like', $term)
                    ->orWhereHas('hotel', fn (Builder $h) => $h->where('guest_name', 'like', $term));
            });
        }

        if (! empty($filters['from'])) {
            $query->whereHas('hotel', fn (Builder $h) => $h->where('check_in', '>=', $filters['from']));
        }

        if (! empty($filters['to'])) {
            $query->whereHas('hotel', fn (Builder $h) => $h->where('check_in', '<=', $filters['to']));
        }

        return $this->applyTab($query, $filters['tab'] ?? 'all');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(Organization $organization, Membership $actor, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($organization, $actor, $filters)
            ->latest('id')
            ->paginate(min(max($perPage, 1), 100));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function tabCounts(Organization $organization, Membership $actor, array $filters): array
    {
        $base = Arr::only($filters, ['hotel_id']);
        $counts = [];

        foreach (self::TABS as $tab) {
            $counts[$tab] = $this->query($organization, $actor, $base + ['tab' => $tab])->count();
        }

        return $counts;
    }

    public function find(Organization $organization, Membership $actor, int $bookingId): Booking
    {
        return $this->query($organization, $actor)
            ->with('statusHistories')
            ->findOrFail($bookingId);
    }

    public function checkIn(Booking $booking, Membership $membership, ?string $roomNumber = null): Booking
    {
        return DB::transaction(function () use ($booking, $membership, $roomNumber) {
            $stay = $this->lockStay($booking);
            $current = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

            if ($current->status !== Booking::STATUS_CONFIRMED) {
                throw new BusinessRuleException('Only a confirmed booking can be checked in.');
            }

            if ($stay->checked_in_at !== null) {
                throw new BusinessRuleException('The guest is already checked in.');
            }

            $today = CarbonImmutable::today();

            if ($today->lt(CarbonImmutable::parse($stay->check_in->toDateString()))) {
                throw new BusinessRuleException('Check-in is only possible from the arrival date.');
            }

            if ($today->gte(CarbonImmutable::parse($stay->check_out->toDateString()))) {
                throw new BusinessRuleException('The stay has already ended.');
            }

            $stay->forceFill([
                'checked_in_at' => now(),
                'checked_in_by_membership_id' => $membership->getKey(),
                'room_number' => $roomNumber,
            ])->save();

            return $current->load(['hotel.roomType', 'hotelProperty:id,name,city', 'statusHistories']);
        });
    }

   public function checkOut(Booking $booking, User $actor, Membership $membership): Booking
{
    return DB::transaction(function () use ($booking, $actor, $membership) {
        $stay = $this->lockStay($booking);
        $current = Booking::query()->lockForUpdate()->findOrFail($booking->getKey());

        if ($current->status !== Booking::STATUS_CONFIRMED) {
            throw new BusinessRuleException('Only a confirmed booking can be checked out.');
        }

        if ($stay->checked_in_at === null) {
            throw new BusinessRuleException('The guest has not been checked in.');
        }

        if ($stay->checked_out_at !== null) {
            throw new BusinessRuleException('The guest is already checked out.');
        }

        $stay->forceFill([
            'checked_out_at' => now(),
            'checked_out_by_membership_id' => $membership->getKey(),
        ])->save();

        $current->forceFill([
            'status' => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        $current->statusHistories()->create([
            'from_status' => Booking::STATUS_CONFIRMED,
            'to_status' => Booking::STATUS_COMPLETED,
            'actor_user_id' => $actor->getKey(),
            'actor_membership_id' => $membership->getKey(),
            'note' => 'Guest checked out',
        ]);

        // ── جديد: تسجيل القيود المالية (idempotent + داخل نفس الـ transaction)
        // أي استثناء هنا يُلغي كل ما سبق (rollback كامل).
        app(\App\Services\Finance\PartnerLedgerService::class)
            ->recordBookingSale($current, $actor->getKey());

        return $current->load(['hotel.roomType', 'hotelProperty:id,name,city', 'statusHistories']);
    });
}

    /**
     * @return array<int, string>
     */
    public function exportColumns(bool $withContact): array
    {
        $columns = [
            'reference', 'status', 'hotel', 'room_type', 'guest_name', 'check_in', 'check_out',
            'nights', 'rooms', 'payment_timing', 'total', 'currency', 'room_number',
            'checked_in_at', 'checked_out_at',
        ];

        return $withContact ? array_merge($columns, ['contact_name', 'contact_email', 'contact_phone']) : $columns;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Generator<int, array<string, mixed>>
     */
    public function exportRows(Organization $organization, Membership $actor, array $filters, bool $withContact): Generator
    {
        foreach ($this->query($organization, $actor, $filters)->lazyById(500) as $booking) {
            $stay = $booking->hotel;

            $row = [
                'reference' => $booking->reference,
                'status' => $booking->status,
                'hotel' => $this->label($booking->hotelProperty?->name),
                'room_type' => $this->label($stay?->roomType?->name),
                'guest_name' => $stay?->guest_name,
                'check_in' => $stay?->check_in?->toDateString(),
                'check_out' => $stay?->check_out?->toDateString(),
                'nights' => $stay?->nights,
                'rooms' => $stay?->rooms,
                'payment_timing' => $stay?->payment_timing,
                'total' => number_format($booking->total_cents / 100, 2, '.', ''),
                'currency' => $booking->currency,
                'room_number' => $stay?->room_number,
                'checked_in_at' => $stay?->checked_in_at?->toDateTimeString(),
                'checked_out_at' => $stay?->checked_out_at?->toDateTimeString(),
            ];

            if ($withContact) {
                $row['contact_name'] = $booking->contact_name;
                $row['contact_email'] = $booking->contact_email;
                $row['contact_phone'] = $booking->contact_phone;
            }

            yield $row;
        }
    }

    private function applyTab(Builder $query, string $tab): Builder
    {
        $today = today()->toDateString();

        return match ($tab) {
            'arriving_today' => $query->where('status', Booking::STATUS_CONFIRMED)
                ->whereHas('hotel', fn (Builder $h) => $h->where('check_in', $today)->whereNull('checked_in_at')),
            'in_house' => $query->where('status', Booking::STATUS_CONFIRMED)
                ->whereHas('hotel', fn (Builder $h) => $h->whereNotNull('checked_in_at')->whereNull('checked_out_at')),
            'upcoming' => $query->where('status', Booking::STATUS_CONFIRMED)
                ->whereHas('hotel', fn (Builder $h) => $h->where('check_in', '>', $today)->whereNull('checked_in_at')),
            'cancelled' => $query->whereIn('status', [Booking::STATUS_CANCELLED, Booking::STATUS_REFUNDED]),
            default => $query,
        };
    }

    private function lockStay(Booking $booking): HotelBooking
    {
        return HotelBooking::query()
            ->where('booking_id', $booking->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<string, string>|string|null  $translated
     */
    private function label(array|string|null $translated): ?string
    {
        if (is_array($translated)) {
            return $translated[app()->getLocale()] ?? $translated['en'] ?? (reset($translated) ?: null);
        }

        return $translated;
    }
}
