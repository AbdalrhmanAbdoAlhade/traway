<?php

namespace Tests\Feature\Authorization;

use App\Models\Booking;
use App\Models\Coupon;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Role;
use App\Models\RoomRate;
use App\Models\RoomType;
use App\Models\User;
use App\Models\VisaApplication;
use App\Models\VisaOffering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class PolicyTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
    }

    private function email(string $prefix): string
    {
        return $prefix.(++$this->counter).'@example.test';
    }

    /**
     * @return array{0: Organization, 1: User, 2: Membership}
     */
    private function organizationWithOwner(string $type = Organization::TYPE_COMPANY): array
    {
        $organization = $this->createOrganization($type);
        $owner = $this->createUser($this->email('owner'));
        $membership = $this->createMembership($owner, $organization, owner: true);

        $this->attachRoleByName($owner, $organization, match ($type) {
            Organization::TYPE_HOTEL => 'hotel_owner',
            Organization::TYPE_SERVICE_PROVIDER => 'service_provider_owner',
            default => 'company_owner',
        });

        return [$organization, $owner, $membership];
    }

    private function booking(Organization $organization, User $customer): Booking
    {
        return Booking::create([
            'reference' => 'TRW-FLT-'.strtoupper(Str::random(8)),
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $customer->getKey(),
            'organization_id' => $organization->getKey(),
            'total_cents' => 10000,
        ]);
    }

    public function test_an_owner_can_view_and_update_a_booking_of_their_organization(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $booking = $this->booking($organization, $this->createUser($this->email('customer')));

        $this->assertTrue(Gate::forUser($owner)->allows('view', $booking));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $booking));
    }

    public function test_an_employee_without_a_role_is_denied(): void
    {
        [$organization] = $this->organizationWithOwner();
        $employee = $this->createUser($this->email('employee'));
        $this->createMembership($employee, $organization);

        $booking = $this->booking($organization, $this->createUser($this->email('customer')));

        $this->assertFalse(Gate::forUser($employee)->allows('view', $booking));
    }

    public function test_a_role_grants_access_only_inside_its_own_tenant(): void
    {
        [$organizationA, $ownerA] = $this->organizationWithOwner();
        [$organizationB] = $this->organizationWithOwner();

        $employee = $this->createUser($this->email('employee'));
        $this->createMembership($employee, $organizationA);
        $this->attachRoleByName($employee, $organizationA, 'bookings_employee');

        $ownBooking = $this->booking($organizationA, $this->createUser($this->email('customer')));
        $foreignBooking = $this->booking($organizationB, $this->createUser($this->email('customer')));

        $this->assertTrue(Gate::forUser($employee)->allows('view', $ownBooking));
        $this->assertFalse(Gate::forUser($employee)->allows('view', $foreignBooking));
        $this->assertFalse(Gate::forUser($employee)->allows('refund', $ownBooking));

        // The owner of tenant A never reaches tenant B's booking either.
        $this->assertFalse(Gate::forUser($ownerA)->allows('view', $foreignBooking));
    }

    public function test_an_employee_cannot_view_or_edit_another_tenants_membership(): void
    {
        [$organizationA, $ownerA] = $this->organizationWithOwner();
        [$organizationB] = $this->organizationWithOwner();

        $foreignMembership = $organizationB->memberships()->firstOrFail();

        $this->assertFalse(Gate::forUser($ownerA)->allows('view', $foreignMembership));
        $this->assertFalse(Gate::forUser($ownerA)->allows('update', $foreignMembership));
        $this->assertFalse(Gate::forUser($ownerA)->allows('deactivate', $foreignMembership));
    }

    public function test_a_suspended_membership_loses_all_access(): void
    {
        [$organization, , $membership] = $this->organizationWithOwner();
        $employee = $this->createUser($this->email('employee'));
        $employeeMembership = $this->createMembership($employee, $organization);
        $this->attachRoleByName($employee, $organization, 'bookings_employee');

        $booking = $this->booking($organization, $this->createUser($this->email('customer')));
        $this->assertTrue(Gate::forUser($employee)->allows('view', $booking));

        $employeeMembership->forceFill(['status' => Membership::STATUS_SUSPENDED])->save();
        $this->tenantResolver()->forget($employee);

        $this->assertFalse(Gate::forUser($employee)->allows('view', $booking));
    }

    public function test_a_suspended_organization_loses_all_access_even_with_an_active_membership(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();

        $booking = $this->booking($organization, $this->createUser($this->email('customer')));
        $this->assertTrue(Gate::forUser($owner)->allows('view', $booking));

        $organization->forceFill(['status' => Organization::STATUS_SUSPENDED])->save();
        $this->tenantResolver()->forget($owner);

        $this->assertFalse(Gate::forUser($owner)->allows('view', $booking));
        $this->assertFalse(Gate::forUser($owner)->allows('create', Booking::class));
    }

    public function test_a_suspended_user_account_loses_all_access(): void
    {
        [$organization, $owner] = $this->organizationWithOwner();
        $booking = $this->booking($organization, $this->createUser($this->email('customer')));

        $owner->forceFill(['status' => 'suspended'])->save();
        $this->tenantResolver()->forget($owner);

        $this->assertFalse(Gate::forUser($owner)->allows('view', $booking));
    }

    public function test_a_hotel_employee_restricted_to_a_property_cannot_touch_another_property(): void
    {
        [$organization] = $this->organizationWithOwner(Organization::TYPE_HOTEL);

        $employee = $this->createUser($this->email('employee'));
        $membership = $this->createMembership($employee, $organization);
        $this->attachRoleByName($employee, $organization, 'hotel_bookings_manager');

        $hotelA = $organization->hotels()->create(['name' => ['en' => 'A'], 'city' => 'Amman']);
        $hotelB = $organization->hotels()->create(['name' => ['en' => 'B'], 'city' => 'Aqaba']);

        $rateA = $this->roomRate($hotelA);
        $rateB = $this->roomRate($hotelB);

        $membership->hotelScopes()->create(['hotel_id' => $hotelA->getKey()]);
        $membership->unsetRelation('hotelScopes');
        $this->tenantResolver()->forget($employee);

        $this->assertTrue(Gate::forUser($employee)->allows('update', $rateA));
        $this->assertFalse(Gate::forUser($employee)->allows('update', $rateB));

        // And never a property of another organization at all.
        [$otherOrganization] = $this->organizationWithOwner(Organization::TYPE_HOTEL);
        $foreignHotel = $otherOrganization->hotels()->create(['name' => ['en' => 'C'], 'city' => 'Irbid']);
        $this->assertFalse(Gate::forUser($employee)->allows('update', $this->roomRate($foreignHotel)));
    }

    public function test_company_staff_can_review_but_never_decide_a_visa_application(): void
    {
        [$company, $companyOwner] = $this->organizationWithOwner();
        $customer = $this->createUser($this->email('customer'));

        $offering = VisaOffering::create([
            'organization_id' => $company->getKey(),
            'country_code' => 'AE',
            'visa_type' => 'tourist',
            'name' => ['en' => 'UAE tourist'],
            'price_cents' => 10000,
        ]);

        $application = VisaApplication::create([
            'user_id' => $customer->getKey(),
            'visa_offering_id' => $offering->getKey(),
            'organization_id' => $company->getKey(),
            'applicant_name' => 'Applicant',
            'price_cents' => 10000,
        ]);

        $this->assertTrue(Gate::forUser($companyOwner)->allows('review', $application));
        $this->assertFalse(Gate::forUser($companyOwner)->allows('decide', $application));
        $this->assertFalse(Gate::forUser($companyOwner)->allows('assign', $application));

        $officer = $this->createUser($this->email('officer'));
        $this->createMembership($officer, $this->platform);
        $this->attachRoleByName($officer, $this->platform, 'platform_visa_officer');

        $this->assertTrue(Gate::forUser($officer)->allows('decide', $application));
        $this->assertTrue(Gate::forUser($officer)->allows('review', $application));
    }

    public function test_platform_staff_can_view_any_booking_but_cannot_mutate_it(): void
    {
        [$company] = $this->organizationWithOwner();
        $booking = $this->booking($company, $this->createUser($this->email('customer')));

        $this->assertTrue(Gate::forUser($this->platformAdmin)->allows('view', $booking));
        $this->assertFalse(Gate::forUser($this->platformAdmin)->allows('update', $booking));
        $this->assertFalse(Gate::forUser($this->platformAdmin)->allows('cancel', $booking));
        $this->assertFalse(Gate::forUser($this->platformAdmin)->allows('refund', $booking));
    }

    public function test_a_platform_coupon_is_invisible_to_a_partner_tenant(): void
    {
        [, $owner] = $this->organizationWithOwner();

        $platformCoupon = Coupon::create([
            'organization_id' => null,
            'code' => 'TRAWAY10',
            'discount_type' => Coupon::TYPE_PERCENTAGE,
            'discount_value' => 1000,
        ]);

        $this->assertFalse(Gate::forUser($owner)->allows('view', $platformCoupon));
        $this->assertFalse(Gate::forUser($owner)->allows('update', $platformCoupon));
        $this->assertTrue(Gate::forUser($this->platformAdmin)->allows('view', $platformCoupon));
    }

    public function test_a_partner_manages_its_own_coupon(): void
    {
        [$company, $owner] = $this->organizationWithOwner();

        $coupon = Coupon::create([
            'organization_id' => $company->getKey(),
            'code' => 'SUMMER25',
            'discount_type' => Coupon::TYPE_FIXED,
            'discount_value' => 500,
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $coupon));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $coupon));
    }

    public function test_a_partner_only_sees_customers_who_transacted_with_it(): void
    {
        [$company, $owner] = $this->organizationWithOwner();
        [$otherCompany] = $this->organizationWithOwner();

        $customer = $this->createUser($this->email('customer'));
        $stranger = $this->createUser($this->email('stranger'));

        $this->booking($company, $customer);
        $this->booking($otherCompany, $stranger);

        $this->assertTrue(Gate::forUser($owner)->allows('view', $customer));
        $this->assertFalse(Gate::forUser($owner)->allows('view', $stranger));
        $this->assertTrue(Gate::forUser($this->platformAdmin)->allows('view', $stranger));
    }

    public function test_payment_access_follows_the_booking_tenant(): void
    {
        [$company, $owner] = $this->organizationWithOwner();
        [$otherCompany] = $this->organizationWithOwner();
        $customer = $this->createUser($this->email('customer'));

        $ownPayment = $this->payment($this->booking($company, $customer));
        $foreignPayment = $this->payment($this->booking($otherCompany, $customer));

        $this->assertTrue(Gate::forUser($owner)->allows('view', $ownPayment));
        $this->assertFalse(Gate::forUser($owner)->allows('view', $foreignPayment));
        $this->assertTrue(Gate::forUser($this->platformAdmin)->allows('view', $foreignPayment));
    }

    public function test_role_policy_protects_system_and_template_roles(): void
    {
        [$company, $owner] = $this->organizationWithOwner();
        $this->attachRoleByName($owner, $company, 'company_owner');

        $ownerRole = Role::query()
            ->where('name', 'company_owner')
            ->where('organization_id', $company->getKey())
            ->firstOrFail();

        $customRole = Role::create([
            'name' => 'custom_viewer',
            'guard_name' => 'web',
            'organization_id' => $company->getKey(),
            'family' => Role::FAMILY_ORGANIZATION,
        ]);

        $templateRole = Role::query()->where('name', 'company_owner')->whereNull('organization_id')->firstOrFail();

        $this->assertFalse(Gate::forUser($owner)->allows('update', $ownerRole));
        $this->assertFalse(Gate::forUser($owner)->allows('delete', $ownerRole));
        $this->assertFalse(Gate::forUser($owner)->allows('update', $templateRole));
        $this->assertTrue(Gate::forUser($owner)->allows('update', $customRole));

        $this->assertTrue(Gate::forUser($owner)->allows('assignPermissions', [$customRole, ['bookings.view']]));
        // employees.create is non-delegable, so it can never be added to an employee role.
        $this->assertFalse(Gate::forUser($owner)->allows('assignPermissions', [$customRole, ['employees.create']]));
    }

    public function test_a_user_with_several_organizations_must_name_the_active_one(): void
    {
        [$organizationA] = $this->organizationWithOwner();
        [$organizationB] = $this->organizationWithOwner();

        $multi = $this->createUser($this->email('multi'));
        $membershipA = $this->createMembership($multi, $organizationA);
        $this->createMembership($multi, $organizationB);
        $this->attachRoleByName($multi, $organizationA, 'bookings_employee');

        // Ambiguous tenant: no decision is taken for a guessed organization.
        $this->assertFalse(Gate::forUser($multi)->allows('viewAny', Booking::class));

        // A token bound to one membership selects that tenant.
        $this->bindTokenTo($multi, $membershipA);
        $this->assertTrue(Gate::forUser($multi)->allows('viewAny', Booking::class));
    }

    public function test_guests_are_denied_every_policy(): void
    {
        $guest = new User(['name' => 'Guest', 'email' => $this->email('guest')]);

        $this->assertFalse(Gate::forUser($guest)->allows('viewAny', Booking::class));
        $this->assertFalse(Gate::forUser($guest)->allows('viewAny', Organization::class));
    }

    private function roomRate(\App\Models\Hotel $hotel): RoomRate
    {
        $roomType = RoomType::create([
            'hotel_id' => $hotel->getKey(),
            'name' => ['en' => 'Double'],
            'max_adults' => 2,
            'max_occupancy' => 3,
            'total_units' => 5,
            'base_price_cents' => 10000,
        ]);

        return RoomRate::create([
            'room_type_id' => $roomType->getKey(),
            'date' => '2026-12-01',
            'price_cents' => 12000,
            'available_units' => 4,
        ]);
    }

    private function payment(Booking $booking): Payment
    {
        return Payment::create([
            'reference' => 'PAY-'.strtoupper(Str::random(10)),
            'booking_id' => $booking->getKey(),
            'user_id' => $booking->user_id,
            'provider' => 'fake',
            'idempotency_key' => (string) Str::uuid(),
            'amount_cents' => 10000,
        ]);
    }
}
