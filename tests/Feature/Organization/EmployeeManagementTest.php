<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Services\Organization\InvitationResult;
use App\Services\Organization\MembershipService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private MembershipService $memberships;

    private Organization $organization;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
        $this->memberships = app(MembershipService::class);

        $this->organization = $this->createOrganization();
        $this->owner = $this->createUser('owner@example.test');
        $this->createMembership($this->owner, $this->organization, owner: true);
        $this->attachRoleByName($this->owner, $this->organization, 'company_owner');
    }

    public function test_an_invitation_is_hashed_and_can_be_accepted(): void
    {
        $invitee = $this->createUser('invitee@example.test');

        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee, jobTitle: 'Agent');

        $stored = $invitation->membership->refresh();

        $this->assertSame(Membership::STATUS_INVITED, $stored->status);
        $this->assertNotSame($invitation->plainToken, $stored->getAttribute('invitation_token_hash'));
        $this->assertSame(hash('sha256', $invitation->plainToken), $stored->getAttribute('invitation_token_hash'));
        $this->assertNull($stored->accepted_at);

        // A pending invitation grants nothing.
        $this->assertFalse(Gate::forUser($invitee)->allows('viewAny', Booking::class));

        $accepted = $this->memberships->acceptInvitation($invitee, $invitation->plainToken);

        $this->assertSame(Membership::STATUS_ACTIVE, $accepted->status);
        $this->assertNotNull($accepted->accepted_at);
        $this->assertNull($accepted->invitation_token_hash);
    }

    public function test_an_invitation_with_a_role_grants_that_role_after_acceptance(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $role = $this->roleInOrganization('bookings_employee');

        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee, $role);
        $this->memberships->acceptInvitation($invitee, $invitation->plainToken);

        $booking = $this->booking($this->organization);

        $this->assertTrue(Gate::forUser($invitee)->allows('view', $booking));
        $this->assertFalse(Gate::forUser($invitee)->allows('refund', $booking));
    }

    public function test_a_pending_invitation_cannot_be_duplicated(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $this->memberships->invite($this->owner, $this->organization, $invitee);

        $this->expectException(BusinessRuleException::class);

        $this->memberships->invite($this->owner, $this->organization, $invitee);
    }

    public function test_a_removed_member_cannot_be_reinvited(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $this->createMembership($invitee, $this->organization, status: Membership::STATUS_REMOVED);

        $this->expectException(BusinessRuleException::class);

        $this->memberships->invite($this->owner, $this->organization, $invitee);
    }

    public function test_an_expired_invitation_is_rejected(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);

        $invitation->membership->forceFill(['invitation_expires_at' => now()->subHour()])->save();

        $this->expectException(BusinessRuleException::class);

        $this->memberships->acceptInvitation($invitee, $invitation->plainToken);
    }

    public function test_another_account_cannot_accept_the_invitation(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $stranger = $this->createUser('stranger@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);

        $this->expectException(AuthorizationException::class);

        $this->memberships->acceptInvitation($stranger, $invitation->plainToken);
    }

    public function test_an_employee_without_the_permission_cannot_invite(): void
    {
        $employee = $this->createUser('employee@example.test');
        $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');

        $newcomer = $this->createUser('newcomer@example.test');

        $this->expectException(AuthorizationException::class);

        $this->memberships->invite($employee, $this->organization, $newcomer);
    }

    public function test_direct_grants_are_delegation_limited_and_revocable(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $booking = $this->booking($this->organization);

        $this->assertFalse(Gate::forUser($employee)->allows('view', $booking));

        $this->memberships->grantPermission($this->owner, $membership, 'bookings.view');
        $this->assertTrue(Gate::forUser($employee)->allows('view', $booking));

        $this->memberships->revokePermission($this->owner, $membership, 'bookings.view');
        $this->assertFalse(Gate::forUser($employee)->allows('view', $booking));
    }

    public function test_a_non_delegable_permission_cannot_be_granted(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);

        $this->expectException(AuthorizationException::class);

        $this->memberships->grantPermission($this->owner, $membership, 'employees.create');
    }

    public function test_an_employee_cannot_grant_permissions(): void
    {
        $employee = $this->createUser('employee@example.test');
        $employeeMembership = $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');

        $target = $this->createUser('target@example.test');
        $targetMembership = $this->createMembership($target, $this->organization);

        $this->expectException(AuthorizationException::class);

        $this->memberships->grantPermission($employee, $targetMembership, 'bookings.view');
    }

    public function test_a_role_containing_a_non_delegable_permission_cannot_be_assigned(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);

        $role = Role::create([
            'name' => 'shadow_owner',
            'guard_name' => 'web',
            'organization_id' => $this->organization->getKey(),
            'family' => Role::FAMILY_ORGANIZATION,
        ]);
        $role->syncPermissions(['employees.create']);

        $this->expectException(AuthorizationException::class);

        $this->memberships->assignRole($this->owner, $membership, $role);
    }

    public function test_a_platform_role_cannot_be_assigned_inside_a_tenant(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);

        $platformRole = Role::query()
            ->where('name', 'platform_super_admin')
            ->where('organization_id', $this->platform->getKey())
            ->firstOrFail();

        $this->expectException(BusinessRuleException::class);

        $this->memberships->assignRole($this->owner, $membership, $platformRole);
    }

    public function test_suspension_revokes_tokens_and_reinstatement_restores_access(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');
        $this->bindTokenTo($employee, $membership);

        $booking = $this->booking($this->organization);
        $this->assertTrue(Gate::forUser($employee)->allows('view', $booking));

        $this->memberships->suspend($this->owner, $membership);

        $this->assertSame(0, PersonalAccessToken::query()->where('membership_id', $membership->getKey())->count());
        $this->assertFalse(Gate::forUser($employee)->allows('view', $booking));

        $this->memberships->reinstate($this->owner, $membership);

        $this->assertTrue(Gate::forUser($employee)->allows('view', $booking));
    }

    public function test_removal_clears_roles_grants_scopes_and_tokens(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');
        $this->memberships->grantPermission($this->owner, $membership, 'customers.view');
        $this->memberships->updateScopes($this->owner, $membership, ['flights'], []);
        $this->bindTokenTo($employee, $membership);

        $this->memberships->remove($this->owner, $membership);

        $this->assertSame(Membership::STATUS_REMOVED, $membership->refresh()->status);
        $this->assertSame(0, DB::table('model_has_roles')
            ->where('model_id', $employee->getKey())
            ->where('organization_id', $this->organization->getKey())
            ->count());
        $this->assertSame(0, DB::table('membership_permissions')->where('membership_id', $membership->getKey())->count());
        $this->assertSame(0, DB::table('membership_service_scopes')->where('membership_id', $membership->getKey())->count());
        $this->assertSame(0, PersonalAccessToken::query()->where('membership_id', $membership->getKey())->count());
        $this->assertFalse(Gate::forUser($employee)->allows('viewAny', Booking::class));
    }

    public function test_service_and_property_scopes_are_validated(): void
    {
        $hotelOrganization = $this->createOrganization(Organization::TYPE_HOTEL);
        $hotelOwner = $this->createUser('hotel-owner@example.test');
        $this->createMembership($hotelOwner, $hotelOrganization, owner: true);
        $this->attachRoleByName($hotelOwner, $hotelOrganization, 'hotel_owner');

        $employee = $this->createUser('reception@example.test');
        $membership = $this->createMembership($employee, $hotelOrganization);

        $ownHotel = $hotelOrganization->hotels()->create(['name' => ['en' => 'Own'], 'city' => 'Amman']);
        $foreignHotel = $this->createOrganization(Organization::TYPE_HOTEL)
            ->hotels()->create(['name' => ['en' => 'Foreign'], 'city' => 'Aqaba']);

        try {
            $this->memberships->updateScopes($hotelOwner, $membership, ['hotels'], [$foreignHotel->getKey()]);
            $this->fail('A property scope from another organization must be rejected.');
        } catch (BusinessRuleException) {
            // expected
        }

        try {
            $this->memberships->updateScopes($hotelOwner, $membership, ['spaceships'], []);
            $this->fail('An unknown service scope must be rejected.');
        } catch (BusinessRuleException) {
            // expected
        }

        $updated = $this->memberships->updateScopes($hotelOwner, $membership, ['hotels'], [$ownHotel->getKey()]);

        $this->assertSame(['hotels'], $updated->services());
        $this->assertSame([$ownHotel->getKey()], $updated->hotelIds());
    }

    public function test_platform_staff_can_transfer_a_non_owner_employee_to_another_organization(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');
        $this->bindTokenTo($employee, $membership);

        $destination = $this->createOrganization();

        $moved = $this->memberships->transfer($this->platformAdmin, $membership, $destination);

        $this->assertSame($destination->getKey(), $moved->organization_id);
        $this->assertSame(0, DB::table('model_has_roles')
            ->where('model_id', $employee->getKey())
            ->where('organization_id', $this->organization->getKey())
            ->count());
        $this->assertSame(0, PersonalAccessToken::query()->where('membership_id', $membership->getKey())->count());
    }

    public function test_a_transfer_is_refused_when_a_membership_already_exists(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $destination = $this->createOrganization();
        $this->createMembership($employee, $destination);

        $this->expectException(BusinessRuleException::class);

        $this->memberships->transfer($this->platformAdmin, $membership, $destination);
    }

    public function test_an_owner_of_the_source_alone_cannot_transfer_an_employee(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $destination = $this->createOrganization();

        $this->expectException(AuthorizationException::class);

        $this->memberships->transfer($this->owner, $membership, $destination);
    }

    private function roleInOrganization(string $name): Role
    {
        return Role::query()
            ->where('name', $name)
            ->where('organization_id', $this->organization->getKey())
            ->firstOrFail();
    }

    private function booking(Organization $organization): Booking
    {
        return Booking::create([
            'reference' => 'TRW-FLT-'.strtoupper(Str::random(8)),
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $this->createUser('customer-'.Str::random(6).'@example.test')->getKey(),
            'organization_id' => $organization->getKey(),
            'total_cents' => 10000,
        ]);
    }
}
