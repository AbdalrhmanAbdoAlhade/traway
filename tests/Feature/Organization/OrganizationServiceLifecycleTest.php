<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationService;
use App\Models\Permission;
use App\Models\Role;
use App\Models\StatusHistory;
use App\Models\User;
use App\Services\Organization\OrganizationServiceLifecycle;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrganizationServiceLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private int $seq = 0;

    private function organization(string $type = Organization::TYPE_SERVICE_PROVIDER): Organization
    {
        $this->seq++;

        return Organization::create([
            'type' => $type,
            'name' => 'Org '.$this->seq,
            'slug' => 'org-'.$this->seq,
            'status' => Organization::STATUS_ACTIVE,
        ]);
    }

    private function user(string $email): User
    {
        return User::create([
            'name' => $email,
            'email' => $email,
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    private function grantRole(User $user, Organization $organization, string $permissionName): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => $permissionName, 'guard_name' => 'web'],
            [
                'family' => $organization->isPlatform() ? Permission::FAMILY_PLATFORM : Permission::FAMILY_ORGANIZATION,
                'allowed_org_types' => $organization->isPlatform() ? null : ['company', 'hotel', 'service_provider'],
                'scope_requirement' => $organization->isPlatform() ? 'platform_global' : 'organization',
            ]
        );

        $role = Role::create([
            'name' => 'role-'.$this->seq.'-'.$permissionName,
            'guard_name' => 'web',
            'organization_id' => $organization->id,
            'family' => $organization->isPlatform() ? Role::FAMILY_PLATFORM : Role::FAMILY_ORGANIZATION,
        ]);
        $role->syncPermissions([$permission]);

        DB::table('model_has_roles')->insert([
            'role_id' => $role->id,
            'model_type' => $user->getMorphClass(),
            'model_id' => $user->id,
            'organization_id' => $organization->id,
        ]);
    }

    private function owner(Organization $organization, string $email): User
    {
        $user = $this->user($email);
        Membership::create([
            'user_id' => $user->id,
            'organization_id' => $organization->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => true,
        ]);
        $this->grantRole($user, $organization, 'org.services.request');

        return $user;
    }

    private function platformAdmin(): User
    {
        $platform = Organization::create([
            'type' => Organization::TYPE_PLATFORM,
            'name' => 'Traway',
            'slug' => 'traway',
            'status' => Organization::STATUS_ACTIVE,
        ]);
        $admin = $this->user('platform@example.test');
        Membership::create([
            'user_id' => $admin->id,
            'organization_id' => $platform->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => true,
        ]);
        $this->grantRole($admin, $platform, 'platform.partners.update');

        return $admin;
    }

    public function test_owner_can_request_a_service_but_cannot_enable_it(): void
    {
        $organization = $this->organization();
        $owner = $this->owner($organization, 'owner@example.test');

        $service = app(OrganizationServiceLifecycle::class)->request($owner, $organization, 'hotels');

        $this->assertSame(OrganizationService::STATUS_REQUESTED, $service->status);
        $this->assertDatabaseHas('status_histories', [
            'subject_type' => $service->getMorphClass(),
            'subject_id' => $service->id,
            'from_status' => null,
            'to_status' => OrganizationService::STATUS_REQUESTED,
        ]);
    }

    public function test_platform_can_approve_and_suspend_a_service_with_effective_cutoff(): void
    {
        $organization = $this->organization();
        $owner = $this->owner($organization, 'owner@example.test');
        $admin = $this->platformAdmin();
        $lifecycle = app(OrganizationServiceLifecycle::class);

        $service = $lifecycle->request($owner, $organization, 'tours');
        $service = $lifecycle->approve($admin, $service);

        $this->assertSame(OrganizationService::STATUS_ENABLED, $service->status);

        $service = $lifecycle->suspend($admin, $service, 'Compliance review');

        $this->assertSame(OrganizationService::STATUS_SUSPENDED, $service->status);
        $this->assertNotNull($service->effective_at);
        $this->assertDatabaseHas('status_histories', [
            'subject_type' => $service->getMorphClass(),
            'subject_id' => $service->id,
            'from_status' => OrganizationService::STATUS_ENABLED,
            'to_status' => OrganizationService::STATUS_SUSPENDED,
        ]);
    }

    public function test_disabled_service_never_becomes_enabled_by_old_membership_scope(): void
    {
        $organization = $this->organization();
        $owner = $this->owner($organization, 'owner@example.test');
        $admin = $this->platformAdmin();
        $lifecycle = app(OrganizationServiceLifecycle::class);

        $service = $lifecycle->request($owner, $organization, 'visas');
        $service = $lifecycle->approve($admin, $service);
        $service = $lifecycle->disable($admin, $service, 'Service discontinued');

        $this->assertSame(OrganizationService::STATUS_DISABLED, $service->status);
        $this->assertNotNull($service->effective_at);
        $this->assertFalse($this->app['App\\Services\\Authorization\\AuthorizationService']->allows(
            $owner,
            'visa_requests.view',
            $organization,
            ['service' => 'visas']
        ));
    }

    public function test_invalid_duplicate_request_and_invalid_transition_are_rejected(): void
    {
        $organization = $this->organization();
        $owner = $this->owner($organization, 'owner@example.test');
        $lifecycle = app(OrganizationServiceLifecycle::class);

        $service = $lifecycle->request($owner, $organization, 'hotels');

        $this->expectException(BusinessRuleException::class);
        $lifecycle->request($owner, $organization, 'hotels');
    }

    public function test_non_owner_cannot_request_a_service(): void
    {
        $organization = $this->organization();
        $employee = $this->user('employee@example.test');
        Membership::create([
            'user_id' => $employee->id,
            'organization_id' => $organization->id,
            'status' => Membership::STATUS_ACTIVE,
            'is_owner' => false,
        ]);
        $this->grantRole($employee, $organization, 'org.services.request');

        $this->expectException(AuthorizationException::class);
        app(OrganizationServiceLifecycle::class)->request($employee, $organization, 'hotels');
    }
}
