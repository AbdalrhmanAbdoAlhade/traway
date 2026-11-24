<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Organization\MembershipService;
use App\Services\Organization\OrganizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class OwnershipTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private OrganizationService $organizations;

    private MembershipService $memberships;

    private AuthorizationService $authz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
        $this->organizations = app(OrganizationService::class);
        $this->memberships = app(MembershipService::class);
        $this->authz = app(AuthorizationService::class);
    }

    public function test_ownership_is_transferred_and_the_previous_owner_loses_owner_powers(): void
    {
        [$organization, $owner, $ownerMembership] = $this->companyWithOwner();
        $employee = $this->createUser('employee@example.test');
        $employeeMembership = $this->createMembership($employee, $organization);
        $this->attachRoleByName($employee, $organization, 'bookings_employee');

        $this->organizations->transferOwnership($owner, $organization, $employeeMembership);

        $this->assertFalse((bool) $ownerMembership->refresh()->is_owner);
        $this->assertTrue((bool) $employeeMembership->refresh()->is_owner);

        // Non-delegable structural permission now belongs to the new owner only.
        $this->assertFalse($this->authz->allows($owner, 'employees.create', $organization));
        $this->assertTrue($this->authz->allows($employee, 'employees.create', $organization));
    }

    public function test_a_non_owner_cannot_transfer_ownership(): void
    {
        [$organization] = $this->companyWithOwner();
        $employee = $this->createUser('employee@example.test');
        $this->createMembership($employee, $organization);

        $target = $this->createUser('target@example.test');
        $targetMembership = $this->createMembership($target, $organization);

        $this->expectException(AuthorizationException::class);

        $this->organizations->transferOwnership($employee, $organization, $targetMembership);
    }

    public function test_the_new_owner_must_belong_to_the_same_organization(): void
    {
        [$organization, $owner] = $this->companyWithOwner();
        $foreignOrganization = $this->createOrganization();
        $foreigner = $this->createUser('foreigner@example.test');
        $foreignMembership = $this->createMembership($foreigner, $foreignOrganization);

        $this->expectException(BusinessRuleException::class);

        $this->organizations->transferOwnership($owner, $organization, $foreignMembership);
    }

    public function test_an_invited_member_cannot_become_owner(): void
    {
        [$organization, $owner] = $this->companyWithOwner();
        $invited = $this->createUser('invited@example.test');
        $invitedMembership = $this->createMembership($invited, $organization, status: Membership::STATUS_INVITED);

        $this->expectException(BusinessRuleException::class);

        $this->organizations->transferOwnership($owner, $organization, $invitedMembership);
    }

    public function test_the_only_owner_cannot_be_removed(): void
    {
        [, , $ownerMembership] = $this->companyWithOwner();

        $this->expectException(BusinessRuleException::class);

        $this->memberships->remove($this->platformAdmin, $ownerMembership);
    }

    public function test_the_only_owner_cannot_be_suspended(): void
    {
        [, , $ownerMembership] = $this->companyWithOwner();

        $this->expectException(BusinessRuleException::class);

        $this->memberships->suspend($this->platformAdmin, $ownerMembership);
    }

    /**
     * @return array{0: Organization, 1: User, 2: Membership}
     */
    private function companyWithOwner(): array
    {
        $organization = $this->createOrganization();
        $owner = $this->createUser('owner@example.test');
        $membership = $this->createMembership($owner, $organization, owner: true);
        $this->attachRoleByName($owner, $organization, 'company_owner');

        return [$organization, $owner, $membership];
    }
}
