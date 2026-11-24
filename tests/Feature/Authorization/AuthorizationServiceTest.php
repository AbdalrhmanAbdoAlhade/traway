<?php

namespace Tests\Feature\Authorization;

use App\Models\Booking;
use App\Models\Membership;
use App\Models\MembershipServiceScope;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthorizationServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthorizationService $authz;

    private int $seq = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authz = app(AuthorizationService::class);
    }

    private function organization(string $type = Organization::TYPE_COMPANY, ?string $name = null): Organization
    {
        $this->seq++;
        $name ??= "Org {$this->seq}";

        return Organization::create([
            'type' => $type,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function user(string $email = 'user@example.test'): User
    {
        return User::create([
            'name' => 'User',
            'email' => $email,
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    private function membership(User $user, Organization $organization, bool $owner = false, string $status = Membership::STATUS_ACTIVE): Membership
    {
        return Membership::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => $status,
            'is_owner' => $owner,
        ]);
    }

    /**
     * @param  array<int, string>|null  $types
     */
    private function permission(
        string $name,
        string $family = Permission::FAMILY_ORGANIZATION,
        ?array $types = null,
        string $scope = 'organization',
    ): Permission {
        return Permission::create([
            'name' => $name,
            'guard_name' => 'web',
            'family' => $family,
            'allowed_org_types' => $types,
            'scope_requirement' => $scope,
        ]);
    }

    /**
     * @param  array<int, Permission>  $permissions
     */
    private function role(Organization $organization, array $permissions, string $name = 'role'): Role
    {
        $role = Role::create([
            'name' => $name,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
            'family' => $organization->isPlatform() ? Role::FAMILY_PLATFORM : Role::FAMILY_ORGANIZATION,
        ]);

        $role->syncPermissions($permissions);

        return $role;
    }

    private function assignRole(User $user, Role $role, Organization $organization): void
    {
        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }

    private function grant(Membership $membership, Permission $permission): void
    {
        DB::table('membership_permissions')->insert([
            'membership_id' => $membership->id,
            'permission_id' => $permission->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_an_owner_holding_a_role_permission_is_allowed(): void
    {
        $org = $this->organization();
        $user = $this->user();
        $membership = $this->membership($user, $org, owner: true);

        $permission = $this->permission('bookings.view');
        $this->assignRole($user, $this->role($org, [$permission]), $org);

        $this->assertTrue($this->authz->allows($user, 'bookings.view', $org));
        $this->assertSame([$permission->name], $this->authz->effectivePermissions($membership));
    }

    public function test_a_user_without_a_membership_is_denied(): void
    {
        $org = $this->organization();
        $outsider = $this->user('outsider@example.test');

        $this->permission('bookings.view');

        $this->assertFalse($this->authz->allows($outsider, 'bookings.view', $org));
    }

    public function test_a_suspended_membership_is_denied_even_with_an_assigned_role(): void
    {
        $org = $this->organization();
        $user = $this->user();
        $this->membership($user, $org, status: Membership::STATUS_SUSPENDED);

        $permission = $this->permission('bookings.view');
        $this->assignRole($user, $this->role($org, [$permission]), $org);

        $this->assertFalse($this->authz->allows($user, 'bookings.view', $org));
    }

    public function test_a_role_from_another_organization_grants_nothing(): void
    {
        $orgA = $this->organization(Organization::TYPE_COMPANY, 'Alpha');
        $orgB = $this->organization(Organization::TYPE_COMPANY, 'Beta');
        $user = $this->user();

        $permission = $this->permission('bookings.view');

        // Role of org B, but the user is only a member of org A.
        $this->membership($user, $orgA);
        $this->assignRole($user, $this->role($orgB, [$permission]), $orgB);

        $this->assertFalse($this->authz->allows($user, 'bookings.view', $orgA));
        $this->assertFalse($this->authz->allows($user, 'bookings.view', $orgB));
    }

    public function test_a_direct_membership_grant_grants_access(): void
    {
        $org = $this->organization();
        $user = $this->user();
        $membership = $this->membership($user, $org);

        $permission = $this->permission('bookings.view');
        $this->grant($membership, $permission);

        $this->assertTrue($this->authz->allows($user, 'bookings.view', $org));
    }

    public function test_non_delegable_permissions_require_owner_status(): void
    {
        $org = $this->organization();
        $user = $this->user();
        $membership = $this->membership($user, $org);

        // employees.create is non-delegable in config/authorization.php.
        $permission = $this->permission('employees.create');
        $this->grant($membership, $permission);

        $this->assertFalse($this->authz->allows($user, 'employees.create', $org));

        $ownerUser = $this->user('owner@example.test');
        $owner = $this->membership($ownerUser, $org, owner: true);
        $this->grant($owner, $permission);

        $this->assertTrue($this->authz->allows($ownerUser, 'employees.create', $org));
    }

    public function test_service_scoped_employees_cannot_act_outside_their_services(): void
    {
        $org = $this->organization(Organization::TYPE_HOTEL);
        $user = $this->user();
        $membership = $this->membership($user, $org);

        $permission = $this->permission('rooms.view', types: ['hotel'], scope: 'service');
        $this->assignRole($user, $this->role($org, [$permission]), $org);

        MembershipServiceScope::create(['membership_id' => $membership->id, 'service' => 'hotels']);
        \App\Models\OrganizationService::create([
            'organization_id' => $org->id,
            'service' => 'hotels',
            'status' => \App\Models\OrganizationService::STATUS_ENABLED,
            'requested_at' => now(),
            'enabled_at' => now(),
        ]);

        $this->assertTrue($this->authz->allows($user, 'rooms.view', $org, ['service' => 'hotels']));
        $this->assertFalse($this->authz->allows($user, 'rooms.view', $org, ['service' => 'tours']));
        $this->assertFalse($this->authz->allows($user, 'rooms.view', $org));
    }

    public function test_employee_with_empty_service_scope_is_denied(): void
    {
        $org = $this->organization(Organization::TYPE_HOTEL);
        $user = $this->user();
        $this->membership($user, $org);

        $permission = $this->permission('rooms.view', types: ['hotel'], scope: 'service');
        $this->assignRole($user, $this->role($org, [$permission]), $org);

        $this->assertFalse($this->authz->allows($user, 'rooms.view', $org, ['service' => 'hotels']));
    }

    public function test_hotel_property_scope_is_enforced(): void
    {
        $org = $this->organization(Organization::TYPE_HOTEL);
        $user = $this->user();
        $membership = $this->membership($user, $org);

        $permission = $this->permission('rates.update', types: ['hotel'], scope: 'property');
        $this->assignRole($user, $this->role($org, [$permission]), $org);

        $hotelA = $org->hotels()->create(['name' => ['en' => 'A'], 'city' => 'Amman']);
        $hotelB = $org->hotels()->create(['name' => ['en' => 'B'], 'city' => 'Aqaba']);

        $membership->hotelScopes()->create(['hotel_id' => $hotelA->id]);
        MembershipServiceScope::create(['membership_id' => $membership->id, 'service' => 'hotels']);

        \App\Models\OrganizationService::create([
            'organization_id' => $org->id,
            'service' => 'hotels',
            'status' => \App\Models\OrganizationService::STATUS_ENABLED,
            'requested_at' => now(),
            'enabled_at' => now(),
        ]);

        $this->assertTrue($this->authz->allows($user, 'rates.update', $org, [
            'service' => 'hotels',
            'hotel_id' => $hotelA->id,
        ]));
        $this->assertFalse($this->authz->allows($user, 'rates.update', $org, [
            'service' => 'hotels',
            'hotel_id' => $hotelB->id,
        ]));
        $this->assertFalse($this->authz->allows($user, 'rates.update', $org, [
            'service' => 'hotels',
        ]));
    }

    public function test_owner_can_access_all_properties_without_employee_scopes(): void
    {
        $org = $this->organization(Organization::TYPE_HOTEL);
        $user = $this->user();
        $this->membership($user, $org, owner: true);

        $permission = $this->permission('rates.view', types: ['hotel'], scope: 'property');
        $this->assignRole($user, $this->role($org, [$permission]), $org);
        $hotel = $org->hotels()->create(['name' => ['en' => 'Owner property'], 'city' => 'Amman']);

        \App\Models\OrganizationService::create([
            'organization_id' => $org->id,
            'service' => 'hotels',
            'status' => \App\Models\OrganizationService::STATUS_ENABLED,
            'requested_at' => now(),
            'enabled_at' => now(),
        ]);

        $this->assertTrue($this->authz->allows($user, 'rates.view', $org, [
            'service' => 'hotels',
            'hotel_id' => $hotel->id,
        ]));
    }

    public function test_a_resource_from_another_tenant_is_denied(): void
    {
        $orgA = $this->organization(Organization::TYPE_COMPANY, 'Alpha');
        $orgB = $this->organization(Organization::TYPE_COMPANY, 'Beta');
        $user = $this->user();
        $customer = $this->user('customer@example.test');

        $this->membership($user, $orgA);
        $permission = $this->permission('bookings.view');
        $this->assignRole($user, $this->role($orgA, [$permission], 'viewer'), $orgA);

        $ownBooking = Booking::create([
            'reference' => 'TRW-FLT-AAAAAAAA',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $customer->id,
            'organization_id' => $orgA->id,
            'total_cents' => 100,
        ]);

        $foreignBooking = Booking::create([
            'reference' => 'TRW-FLT-BBBBBBBB',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $customer->id,
            'organization_id' => $orgB->id,
            'total_cents' => 100,
        ]);

        $this->assertTrue($this->authz->allows($user, 'bookings.view', $orgA, ['resource' => $ownBooking]));
        $this->assertFalse($this->authz->allows($user, 'bookings.view', $orgA, ['resource' => $foreignBooking]));
    }

    public function test_platform_permissions_never_apply_inside_a_partner_tenant(): void
    {
        $company = $this->organization(Organization::TYPE_COMPANY, 'Partner');
        $platform = $this->organization(Organization::TYPE_PLATFORM, 'Traway');
        $user = $this->user();
        $this->membership($user, $company, owner: true);

        $platformPermission = $this->permission('platform.overview.view', Permission::FAMILY_PLATFORM);
        $this->assignRole($user, $this->role($company, [$platformPermission], 'sneaky'), $company);

        $this->assertFalse($this->authz->allows($user, 'platform.overview.view', $company));

        // And an organization permission never applies inside the platform tenant.
        $orgPermission = $this->permission('bookings.view');
        $platformUser = $this->user('staff@example.test');
        $this->membership($platformUser, $platform, owner: true);
        $this->assignRole($platformUser, $this->role($platform, [$orgPermission], 'staff'), $platform);

        $this->assertFalse($this->authz->allows($platformUser, 'bookings.view', $platform));
    }

    public function test_owner_can_delegate_but_not_non_delegable_permissions(): void
    {
        $org = $this->organization();
        $ownerUser = $this->user('owner@example.test');
        $employeeUser = $this->user('employee@example.test');
        $this->membership($ownerUser, $org, owner: true);
        $employee = $this->membership($employeeUser, $org);

        $delegable = $this->permission('bookings.view');
        $nonDelegable = $this->permission('employees.create');
        $notHeld = $this->permission('rates.update', types: ['company']);

        $this->assignRole($ownerUser, $this->role($org, [$delegable, $nonDelegable], 'owner-role'), $org);

        $this->assertTrue($this->authz->canGrant($ownerUser, 'bookings.view', $employee));
        $this->assertFalse($this->authz->canGrant($ownerUser, 'employees.create', $employee));
        $this->assertFalse($this->authz->canGrant($ownerUser, 'rates.update', $employee));
        $this->assertFalse($this->authz->canGrant($employeeUser, 'bookings.view', $employee));
    }

    public function test_permission_cache_is_invalidated_when_grants_change(): void
    {
        $org = $this->organization();
        $user = $this->user();
        $membership = $this->membership($user, $org);

        $permission = $this->permission('bookings.view');

        $this->assertFalse($this->authz->allows($user, 'bookings.view', $org));

        $this->grant($membership, $permission);
        $this->authz->invalidate($membership);

        $this->assertTrue($this->authz->allows($user, 'bookings.view', $org));
    }

    public function test_permissions_are_isolated_between_two_organizations_of_the_same_user(): void
    {
        $orgA = $this->organization(Organization::TYPE_COMPANY, 'Alpha');
        $orgB = $this->organization(Organization::TYPE_COMPANY, 'Beta');
        $user = $this->user();

        $this->membership($user, $orgA);
        $this->membership($user, $orgB);

        $permission = $this->permission('bookings.view');
        $this->assignRole($user, $this->role($orgA, [$permission], 'viewer-a'), $orgA);

        $this->assertTrue($this->authz->allows($user, 'bookings.view', $orgA));
        $this->assertFalse($this->authz->allows($user, 'bookings.view', $orgB));
    }
}
