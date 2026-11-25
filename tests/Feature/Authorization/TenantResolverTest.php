<?php

namespace Tests\Feature\Authorization;

use App\Models\Membership;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class TenantResolverTest extends TestCase
{
    use BuildsTenants;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootTenants();
    }

    public function test_the_organization_header_can_no_longer_select_a_tenant(): void
    {
        $orgA = $this->createOrganization(Organization::TYPE_COMPANY);
        $orgB = $this->createOrganization(Organization::TYPE_COMPANY);
        $user = $this->createUser('multi@example.test');
        $membershipA = $this->createMembership($user, $orgA);
        $this->createMembership($user, $orgB);

        $this->bindTokenTo($user, $membershipA);
        request()->headers->set('X-Organization-Id', (string) $orgB->getKey());
        $this->tenantResolver()->forget($user);

        $this->assertSame($orgA->getKey(), $this->tenantResolver()->organizationIdFor($user));
    }

    public function test_an_unbound_token_with_several_memberships_has_no_tenant_even_with_the_header(): void
    {
        $orgA = $this->createOrganization(Organization::TYPE_COMPANY);
        $orgB = $this->createOrganization(Organization::TYPE_COMPANY);
        $user = $this->createUser('unbound@example.test');
        $this->createMembership($user, $orgA);
        $this->createMembership($user, $orgB);

        $user->withAccessToken($user->createToken('unbound')->accessToken);
        request()->headers->set('X-Organization-Id', (string) $orgA->getKey());
        $this->tenantResolver()->forget($user);

        $this->assertNull($this->tenantResolver()->membershipFor($user));
    }

    public function test_an_unbound_token_with_a_single_membership_resolves_it(): void
    {
        $org = $this->createOrganization(Organization::TYPE_HOTEL);
        $user = $this->createUser('single@example.test');
        $this->createMembership($user, $org);

        $user->withAccessToken($user->createToken('single')->accessToken);
        $this->tenantResolver()->forget($user);

        $this->assertSame($org->getKey(), $this->tenantResolver()->organizationIdFor($user));
    }

    public function test_a_token_bound_to_a_suspended_membership_fails_closed(): void
    {
        $orgA = $this->createOrganization(Organization::TYPE_COMPANY);
        $orgB = $this->createOrganization(Organization::TYPE_COMPANY);
        $user = $this->createUser('suspended@example.test');
        $membershipA = $this->createMembership($user, $orgA);
        $this->createMembership($user, $orgB);

        $this->bindTokenTo($user, $membershipA);
        $membershipA->forceFill(['status' => Membership::STATUS_SUSPENDED])->save();
        $this->tenantResolver()->forget($user);

        $this->assertNull($this->tenantResolver()->membershipFor($user));
    }

    public function test_a_token_bound_to_someone_elses_membership_is_rejected(): void
    {
        $org = $this->createOrganization(Organization::TYPE_COMPANY);
        $owner = $this->createUser('owner@example.test');
        $ownerMembership = $this->createMembership($owner, $org, owner: true);
        $attacker = $this->createUser('attacker@example.test');

        $token = $attacker->createToken('stolen-binding');
        $token->accessToken->forceFill(['membership_id' => $ownerMembership->getKey()])->save();
        $attacker->withAccessToken($token->accessToken);
        $this->tenantResolver()->forget($attacker);

        $this->assertNull($this->tenantResolver()->membershipFor($attacker));
    }

    public function test_login_binds_the_token_when_there_is_one_membership_and_lists_several(): void
    {
        $orgA = $this->createOrganization(Organization::TYPE_COMPANY);
        $orgB = $this->createOrganization(Organization::TYPE_HOTEL);

        $single = $this->createUser('one@example.test');
        $this->createMembership($single, $orgA);

        $multi = $this->createUser('two@example.test');
        $this->createMembership($multi, $orgA);
        $this->createMembership($multi, $orgB);

        $this->postJson('/api/v1/auth/login', ['email' => 'one@example.test', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('organization_id', $orgA->getKey())
            ->assertJsonCount(1, 'memberships');

        $this->postJson('/api/v1/auth/login', ['email' => 'two@example.test', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('organization_id', null)
            ->assertJsonCount(2, 'memberships');
    }

    public function test_switch_organization_issues_a_bound_token_and_rejects_foreign_organizations(): void
    {
        $orgA = $this->createOrganization(Organization::TYPE_COMPANY);
        $orgB = $this->createOrganization(Organization::TYPE_HOTEL);
        $orgForeign = $this->createOrganization(Organization::TYPE_COMPANY);

        $user = $this->createUser('switch@example.test');
        $this->createMembership($user, $orgA);
        $membershipB = $this->createMembership($user, $orgB);

        $plain = $this->postJson('/api/v1/auth/login', [
            'email' => 'switch@example.test',
            'password' => 'password123',
        ])->assertOk()->json('token');

        $this->withToken($plain)
            ->postJson('/api/v1/auth/switch-organization', ['organization_id' => $orgForeign->getKey()])
            ->assertStatus(422);

        $response = $this->withToken($plain)
            ->postJson('/api/v1/auth/switch-organization', ['organization_id' => $orgB->getKey()])
            ->assertOk()
            ->assertJsonPath('organization_id', $orgB->getKey());

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_id' => $user->getKey(),
            'membership_id' => $membershipB->getKey(),
        ]);

        $this->assertNotSame($plain, $response->json('token'));
    }
}
