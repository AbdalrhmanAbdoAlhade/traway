<?php

namespace Tests\Feature\Database;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\PartnerLedgerEntry;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\StatusHistory;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use LogicException;
use Tests\TestCase;

/**
 * These assertions are only meaningful on MySQL. They prove real foreign keys,
 * unique constraints and model-level append-only guarantees (DEC-12): SQLite is
 * never accepted as evidence for these behaviours.
 */
class SchemaIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function organization(string $type = Organization::TYPE_COMPANY): Organization
    {
        static $n = 0;
        $n++;

        return Organization::create([
            'type' => $type,
            'name' => "Org {$n}",
            'slug' => "org-{$n}",
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function user(string $email = 'owner@example.test'): User
    {
        return User::create([
            'name' => 'Owner',
            'email' => $email,
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_expected_tables_exist(): void
    {
        foreach ([
            'organizations', 'organization_applications', 'organization_documents',
            'memberships', 'membership_service_scopes', 'membership_hotel_scopes', 'membership_permissions',
            'hotels', 'room_types', 'room_rates',
            'tours', 'tour_slots', 'tour_itinerary_days', 'tour_pickup_points',
            'visa_offerings', 'visa_applications', 'visa_application_documents',
            'coupons', 'coupon_redemptions', 'checkout_sessions',
            'bookings', 'flight_bookings', 'hotel_bookings', 'tour_bookings', 'esim_orders',
            'payments', 'refunds', 'payout_accounts', 'payout_requests', 'partner_ledger_entries',
            'status_histories',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table: {$table}");
        }

        $this->assertFalse(Schema::hasTable('audit_logs'), 'Audit logs are removed from the architecture.');
    }

    public function test_a_user_cannot_hold_two_memberships_in_one_organization(): void
    {
        $org = $this->organization();
        $user = $this->user();

        Membership::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_ACTIVE,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Membership::create([
            'user_id' => $user->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_INVITED,
        ]);
    }

    public function test_an_organization_can_only_have_one_active_owner(): void
    {
        $org = $this->organization();

        Membership::create([
            'user_id' => $this->user('first@example.test')->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => true,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Membership::create([
            'user_id' => $this->user('second@example.test')->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => true,
        ]);
    }

    public function test_an_invited_owner_does_not_reserve_the_owner_slot(): void
    {
        $org = $this->organization();

        Membership::create([
            'user_id' => $this->user('pending@example.test')->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_INVITED,
            'is_owner' => true,
        ]);

        $this->assertSame(0, Membership::query()->whereNotNull('active_owner_org_id')->count());

        Membership::create([
            'user_id' => $this->user('active@example.test')->id,
            'organization_id' => $org->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => true,
        ]);

        $this->assertSame(1, Membership::query()->whereNotNull('active_owner_org_id')->count());
    }

    public function test_coupon_codes_are_unique_per_issuer_only(): void
    {
        $a = $this->organization();
        $b = $this->organization();

        Coupon::create([
            'organization_id' => null,
            'code' => 'summer25',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 1000,
        ]);

        Coupon::create([
            'organization_id' => $a->id,
            'code' => 'SUMMER25',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 500,
        ]);

        Coupon::create([
            'organization_id' => $b->id,
            'code' => 'SUMMER25',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 250,
        ]);

        $this->assertSame(
            [0, $a->id, $b->id],
            Coupon::query()->where('code', 'SUMMER25')->orderBy('scope_key')->pluck('scope_key')->all()
        );

        $this->expectException(UniqueConstraintViolationException::class);

        Coupon::create([
            'organization_id' => $a->id,
            'code' => 'SUMMER25',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 1,
        ]);
    }

    public function test_a_room_type_has_one_rate_row_per_date(): void
    {
        $hotel = Hotel::create([
            'organization_id' => $this->organization(Organization::TYPE_HOTEL)->id,
            'name' => ['en' => 'Hotel'],
            'city' => 'Amman',
        ]);

        $roomType = RoomType::create([
            'hotel_id' => $hotel->id,
            'name' => ['en' => 'Double'],
            'max_adults' => 2,
            'max_occupancy' => 3,
            'total_units' => 5,
            'base_price_cents' => 10000,
        ]);

        RoomRate::create([
            'room_type_id' => $roomType->id,
            'date' => '2026-12-01',
            'price_cents' => 12000,
            'available_units' => 4,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        RoomRate::create([
            'room_type_id' => $roomType->id,
            'date' => '2026-12-01',
            'price_cents' => 9000,
            'available_units' => 4,
        ]);
    }

    public function test_booking_references_are_unique(): void
    {
        $user = $this->user();

        Booking::create([
            'reference' => 'TRW-FLT-7K3M9QXP',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $user->id,
            'total_cents' => 5000,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Booking::create([
            'reference' => 'TRW-FLT-7K3M9QXP',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $user->id,
            'total_cents' => 5000,
        ]);
    }

    public function test_partner_ledger_entries_are_append_only(): void
    {
        $org = $this->organization();

        $entry = PartnerLedgerEntry::create([
            'organization_id' => $org->id,
            'entry_key' => 'sale:1',
            'type' => PartnerLedgerEntry::TYPE_SALE,
            'amount_cents' => 10000,
        ]);

        $this->assertSame(10000, $org->balanceCents());

        $this->expectException(LogicException::class);
        $entry->update(['amount_cents' => 1]);
    }

    public function test_ledger_entry_keys_are_idempotent(): void
    {
        $org = $this->organization();

        PartnerLedgerEntry::create([
            'organization_id' => $org->id,
            'entry_key' => 'sale:1',
            'type' => PartnerLedgerEntry::TYPE_SALE,
            'amount_cents' => 10000,
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        PartnerLedgerEntry::create([
            'organization_id' => $org->id,
            'entry_key' => 'sale:1',
            'type' => PartnerLedgerEntry::TYPE_SALE,
            'amount_cents' => 10000,
        ]);
    }

    public function test_status_histories_are_append_only(): void
    {
        $user = $this->user();
        $booking = Booking::create([
            'reference' => 'TRW-HOT-ABCDEFGH',
            'service' => Booking::SERVICE_HOTEL,
            'user_id' => $user->id,
            'total_cents' => 1000,
        ]);

        $history = StatusHistory::create([
            'subject_type' => 'booking',
            'subject_id' => $booking->id,
            'to_status' => Booking::STATUS_CONFIRMED,
        ]);

        $this->expectException(LogicException::class);
        $history->update(['to_status' => Booking::STATUS_CANCELLED]);
    }

    public function test_deleting_an_organization_is_restricted_while_hotels_exist(): void
    {
        $org = $this->organization(Organization::TYPE_HOTEL);
        Hotel::create([
            'organization_id' => $org->id,
            'name' => ['en' => 'Hotel'],
            'city' => 'Amman',
        ]);

        $this->expectException(QueryException::class);
        $org->forceDelete();
    }
}
