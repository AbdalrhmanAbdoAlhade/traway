<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\Organization;
use App\Services\Authorization\AuthorizationService;
use App\Services\Organization\MembershipService;
use App\Services\Organization\OrganizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class OrganizationLifecycleTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private OrganizationService $organizations;

    private AuthorizationService $authz;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
        $this->organizations = app(OrganizationService::class);
        $this->authz = app(AuthorizationService::class);
    }

    public function test_suspension_cuts_access_and_revokes_tokens_immediately(): void
    {
        [$organization, $owner, $membership] = $this->companyWithOwner();
        $this->bindTokenTo($owner, $membership);

        $booking = $this->booking($organization);
        $this->assertTrue(Gate::forUser($owner)->allows('view', $booking));
        $versionBefore = (int) $membership->refresh()->permissions_version;

        $this->organizations->suspend($this->platformAdmin, $organization, 'Fraud investigation');

        $this->assertSame(Organization::STATUS_SUSPENDED, $organization->refresh()->status);
        $this->assertSame(0, PersonalAccessToken::query()->where('membership_id', $membership->getKey())->count());
        $this->assertGreaterThan($versionBefore, (int) $membership->refresh()->permissions_version);
        $this->assertFalse($this->authz->allows($owner, 'bookings.view', $organization));
        $this->assertFalse(Gate::forUser($owner)->allows('view', $booking));
    }

    public function test_reinstatement_restores_tenant_access(): void
    {
        [$organization, $owner, $membership] = $this->companyWithOwner();

        $this->organizations->suspend($this->platformAdmin, $organization, 'Review');
        $this->organizations->reinstate($this->platformAdmin, $organization);

        $this->assertSame(Organization::STATUS_ACTIVE, $organization->refresh()->status);
        $this->assertNull($organization->suspended_at);

        $this->tenantResolver()->forget($owner);

        $this->assertTrue($this->authz->allows($owner, 'bookings.view', $organization));
    }

    public function test_a_partner_owner_cannot_suspend_an_organization(): void
    {
        [$organization, $owner] = $this->companyWithOwner();

        $this->expectException(AuthorizationException::class);

        $this->organizations->suspend($owner, $organization, 'I do not like Mondays');
    }

    public function test_the_platform_organization_cannot_be_suspended_or_closed(): void
    {
        $this->expectException(BusinessRuleException::class);

        $this->organizations->suspend($this->platformAdmin, $this->platform, 'No');
    }

    public function test_only_the_owner_can_close_an_organization(): void
    {
        [$organization, $owner, $ownerMembership] = $this->companyWithOwner();
        $employee = $this->createUser('employee@example.test');
        $this->createMembership($employee, $organization);

        try {
            $this->organizations->close($employee, $organization);
            $this->fail('An employee must not close the organization.');
        } catch (AuthorizationException) {
            // expected
        }

        $this->organizations->close($owner, $organization);

        $this->assertSame(Organization::STATUS_CLOSED, $organization->refresh()->status);
        $this->assertFalse($this->authz->allows($owner, 'bookings.view', $organization));
    }

    public function test_an_employee_cannot_suspend_or_remove_the_last_owner(): void
    {
        [$organization, $owner, $ownerMembership] = $this->companyWithOwner();

        $this->expectException(BusinessRuleException::class);

        app(MembershipService::class)->suspend($this->platformAdmin, $ownerMembership);
    }

    public function test_profile_updates_require_the_profile_permission(): void
    {
        [$organization, $owner] = $this->companyWithOwner();

        $updated = $this->organizations->update($owner, $organization, ['name' => 'Renamed Company']);
        $this->assertSame('Renamed Company', $updated->name);

        $employee = $this->createUser('employee@example.test');
        $this->createMembership($employee, $organization);

        $this->expectException(AuthorizationException::class);

        $this->organizations->update($employee, $organization, ['name' => 'Hijacked']);
    }

    /**
     * @return array{0: Organization, 1: \App\Models\User, 2: Membership}
     */
    private function companyWithOwner(): array
    {
        $organization = $this->createOrganization();
        $owner = $this->createUser('owner@example.test');
        $membership = $this->createMembership($owner, $organization, owner: true);
        $this->attachRoleByName($owner, $organization, 'company_owner');

        return [$organization, $owner, $membership];
    }

    private function booking(Organization $organization): Booking
    {
        return Booking::create([
            'reference' => 'TRW-FLT-'.strtoupper(Str::random(8)),
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $this->createUser('customer-'.Str::random(5).'@example.test')->getKey(),
            'organization_id' => $organization->getKey(),
            'total_cents' => 10000,
        ]);
    }
}
