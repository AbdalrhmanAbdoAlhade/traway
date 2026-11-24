<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\Role;
use App\Models\User;
use App\Services\Organization\OrganizationApplicationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class OrganizationApplicationTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    private OrganizationApplicationService $applications;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
        $this->applications = app(OrganizationApplicationService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'type' => Organization::TYPE_COMPANY,
            'business_name' => 'Acme Travel',
            'country_code' => 'JO',
            'city' => 'Amman',
            'responsible_name' => 'Sami Owner',
            'phone' => '+962790000000',
            'email' => 'partner@example.test',
            'terms_accepted' => true,
        ], $overrides);
    }

    public function test_a_partner_can_submit_an_application(): void
    {
        $application = $this->applications->submit($this->payload());

        $this->assertSame(OrganizationApplication::STATUS_SUBMITTED, $application->status);
        $this->assertNotNull($application->terms_accepted_at);
        $this->assertNull($application->organization_id);
    }

    public function test_terms_are_required(): void
    {
        $this->expectException(BusinessRuleException::class);

        $this->applications->submit($this->payload(['terms_accepted' => false]));
    }

    public function test_a_duplicate_pending_application_is_rejected(): void
    {
        $this->applications->submit($this->payload());

        $this->expectException(BusinessRuleException::class);

        $this->applications->submit($this->payload());
    }

    public function test_a_partner_owner_cannot_approve_its_own_application(): void
    {
        [, $owner] = $this->organizationWithPartnerOwner();
        $application = $this->applications->submit($this->payload());

        $this->expectException(AuthorizationException::class);

        $this->applications->approve($owner, $application);
    }

    public function test_platform_approval_creates_a_pending_organization_with_provisioned_roles(): void
    {
        $application = $this->applications->submit($this->payload());

        $result = $this->applications->approve($this->platformAdmin, $application);
        $organization = $result['organization'];

        $this->assertSame(Organization::STATUS_PENDING, $organization->status);
        $this->assertSame(Organization::TYPE_COMPANY, $organization->type);
        $this->assertSame($organization->getKey(), $application->refresh()->organization_id);
        $this->assertSame(OrganizationApplication::STATUS_APPROVED, $application->refresh()->status);
        $this->assertNotEmpty($result['activation_token']);

        $ownerRole = Role::query()
            ->where('name', 'company_owner')
            ->where('organization_id', $organization->getKey())
            ->first();

        $this->assertNotNull($ownerRole, 'The organization must receive its own owner role copy.');
        $this->assertTrue((bool) $ownerRole->is_system);
        $this->assertFalse((bool) $ownerRole->is_template);
    }

    public function test_activation_creates_the_owner_account_and_activates_the_organization(): void
    {
        $application = $this->applications->submit($this->payload());
        $result = $this->applications->approve($this->platformAdmin, $application);

        $membership = $this->applications->activate($result['activation_token'], [
            'name' => 'Sami Owner',
            'password' => 'strong-password',
        ]);

        $organization = $result['organization']->refresh();

        $this->assertSame(Organization::STATUS_ACTIVE, $organization->status);
        $this->assertTrue((bool) $membership->is_owner);
        $this->assertSame(Membership::STATUS_ACTIVE, $membership->status);
        $this->assertSame('partner@example.test', $membership->user->email);
        $this->assertNotNull($organization->approved_at);
        $this->assertNotNull($application->refresh()->activation_used_at);

        // The owner holds the provisioned owner role, so the permission set is live.
        $this->assertTrue(app(\App\Services\Authorization\AuthorizationService::class)
            ->allows($membership->user, 'employees.create', $organization));
    }

    public function test_an_activation_link_can_only_be_used_once(): void
    {
        $application = $this->applications->submit($this->payload());
        $result = $this->applications->approve($this->platformAdmin, $application);

        $this->applications->activate($result['activation_token'], ['name' => 'Owner', 'password' => 'pw-123456']);

        $this->expectException(BusinessRuleException::class);

        $this->applications->activate($result['activation_token'], ['name' => 'Owner', 'password' => 'pw-123456']);
    }

    public function test_an_expired_activation_link_is_rejected(): void
    {
        $application = $this->applications->submit($this->payload());
        $result = $this->applications->approve($this->platformAdmin, $application);

        $application->refresh()->forceFill(['activation_expires_at' => now()->subDay()])->save();

        $this->expectException(BusinessRuleException::class);

        $this->applications->activate($result['activation_token'], ['name' => 'Owner', 'password' => 'pw-123456']);
    }

    public function test_an_existing_account_can_activate_without_a_new_password(): void
    {
        $existing = $this->createUser('partner@example.test');

        $application = $this->applications->submit($this->payload());
        $result = $this->applications->approve($this->platformAdmin, $application);

        $membership = $this->applications->activate($result['activation_token']);

        $this->assertSame($existing->getKey(), $membership->user_id);
        $this->assertSame(Organization::STATUS_ACTIVE, $result['organization']->refresh()->status);
    }

    public function test_a_rejected_application_cannot_be_approved(): void
    {
        $application = $this->applications->submit($this->payload());
        $this->applications->reject($this->platformAdmin, $application, 'Incomplete documents');

        $this->expectException(BusinessRuleException::class);

        $this->applications->approve($this->platformAdmin, $application);
    }

    /**
     * @return array{0: Organization, 1: User}
     */
    private function organizationWithPartnerOwner(): array
    {
        $organization = $this->createOrganization();
        $owner = $this->createUser('company-owner@example.test');
        $this->createMembership($owner, $organization, owner: true);
        $this->attachRoleByName($owner, $organization, 'company_owner');

        return [$organization, $owner];
    }
}
