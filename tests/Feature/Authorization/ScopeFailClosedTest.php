<?php

namespace Tests\Feature\Authorization;

use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class ScopeFailClosedTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private AuthorizationService $authz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
        $this->authz = app(AuthorizationService::class);
    }

    public function test_hotel_query_filter_returns_nothing_for_an_employee_with_no_rows(): void
    {
        [$org, $hotelA, $hotelB] = $this->hotelOrganizationWithTwoHotels();
        $staff = $this->createMembership($this->createUser('staff@example.test'), $org);

        $ids = $this->authz->restrictToHotels(Hotel::query(), $staff, 'id')->pluck('id')->all();

        $this->assertSame([], $ids);
    }

    public function test_hotel_query_filter_returns_only_the_scoped_property(): void
    {
        [$org, $hotelA] = $this->hotelOrganizationWithTwoHotels();
        $staff = $this->createMembership($this->createUser('scoped@example.test'), $org);
        $staff->hotelScopes()->create(['hotel_id' => $hotelA->id]);
        $staff->unsetRelation('hotelScopes');

        $ids = $this->authz->restrictToHotels(Hotel::query(), $staff, 'id')->pluck('id')->all();

        $this->assertSame([$hotelA->id], $ids);
    }

    public function test_the_hotel_owner_sees_every_property_by_ownership(): void
    {
        [$org, $hotelA, $hotelB] = $this->hotelOrganizationWithTwoHotels();
        $owner = $this->createMembership($this->createUser('owner@example.test'), $org, owner: true);

        $ids = $this->authz->restrictToHotels(Hotel::query(), $owner, 'id')->pluck('id')->sort()->values()->all();

        $this->assertSame([$hotelA->id, $hotelB->id], $ids);
    }

    public function test_membership_helpers_fail_closed(): void
    {
        [$org, $hotelA, $hotelB] = $this->hotelOrganizationWithTwoHotels();

        $none = $this->createMembership($this->createUser('none@example.test'), $org);
        $this->assertFalse($none->canAccessHotel($hotelA->id));
        $this->assertFalse($none->canAccessService('hotels'));

        $owner = $this->createMembership($this->createUser('own@example.test'), $org, owner: true);
        $this->assertTrue($owner->canAccessHotel($hotelB->id));
        $this->assertTrue($owner->canAccessService('hotels'));
    }

    public function test_a_hotel_employee_restricted_to_one_property_cannot_update_the_other(): void
    {
        [$org, $hotelA, $hotelB] = $this->hotelOrganizationWithTwoHotels();
        $user = $this->createUser('hotel-staff@example.test');
        $membership = $this->createMembership($user, $org);
        $this->attachRoleByName($user, $org, 'hotel_manager');
        $membership->hotelScopes()->create(['hotel_id' => $hotelA->id]);
        $membership->unsetRelation('hotelScopes');

        $this->assertTrue($this->authz->allows($user, 'org.profile.view', $org, ['resource' => $hotelA]));
        $this->assertFalse($this->authz->allows($user, 'org.profile.view', $org, ['resource' => $hotelB]));
    }

    public function test_the_service_filter_maps_scope_names_to_booking_services(): void
    {
        $org = $this->createOrganization(Organization::TYPE_COMPANY);
        $staff = $this->createMembership($this->createUser('svc@example.test'), $org);
        $staff->serviceScopes()->create(['service' => 'tours']);
        $staff->unsetRelation('serviceScopes');

        $query = $this->authz->restrictToServices(Booking::query(), $staff);

        $this->assertSame(['tour'], $query->getQuery()->bindings['where']);
    }

    public function test_a_restricted_employee_sees_only_assigned_records(): void
    {
        $org = $this->createOrganization(Organization::TYPE_COMPANY);
        $user = $this->createUser('restricted@example.test');
        $membership = $this->createMembership($user, $org);
        $this->attachRoleByName($user, $org, 'restricted_employee');

        $this->assertTrue($this->authz->isAssignedOnly($membership));

        $query = $this->authz->restrictToAssigned(Booking::query(), $membership);

        $this->assertStringContainsString('assigned_membership_id', $query->toSql());
        $this->assertSame([$membership->getKey()], $query->getQuery()->bindings['where']);
    }

    public function test_owners_and_unrestricted_roles_are_not_assignment_filtered(): void
    {
        $org = $this->createOrganization(Organization::TYPE_COMPANY);
        $owner = $this->createMembership($this->createUser('o@example.test'), $org, owner: true);

        $this->assertFalse($this->authz->isAssignedOnly($owner));
        $this->assertStringNotContainsString(
            'assigned_membership_id',
            $this->authz->restrictToAssigned(Booking::query(), $owner)->toSql()
        );
    }

    /**
     * @return array{0: Organization, 1: Hotel, 2: Hotel}
     */
    private function hotelOrganizationWithTwoHotels(): array
    {
        $org = $this->createOrganization(Organization::TYPE_HOTEL);
        $hotelA = $org->hotels()->create(['name' => ['en' => 'A'], 'city' => 'Amman']);
        $hotelB = $org->hotels()->create(['name' => ['en' => 'B'], 'city' => 'Aqaba']);

        return [$org, $hotelA, $hotelB];
    }
}
