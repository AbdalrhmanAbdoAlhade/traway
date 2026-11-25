<?php

namespace Tests\Feature\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Booking;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organization\MembershipService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Concerns\BuildsTenants;
use Tests\TestCase;

class InvitationLifecycleTest extends TestCase
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

    public function test_resending_rotates_the_token_and_kills_the_old_one(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $first = $this->memberships->invite($this->owner, $this->organization, $invitee);

        $second = $this->memberships->resendInvitation(
            $this->owner, $this->organization, $first->membership->getKey()
        );

        $this->assertNotSame($first->plainToken, $second->plainToken);

        try {
            $this->memberships->acceptInvitation($invitee, $first->plainToken);
            $this->fail('The rotated token must not be accepted.');
        } catch (BusinessRuleException) {
            $this->assertTrue(true);
        }

        $accepted = $this->memberships->acceptInvitation($invitee, $second->plainToken);
        $this->assertSame(Membership::STATUS_ACTIVE, $accepted->status);
    }

    public function test_resending_renews_an_expired_invitation(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);
        $invitation->membership->forceFill(['invitation_expires_at' => now()->subDay()])->save();

        $renewed = $this->memberships->resendInvitation(
            $this->owner, $this->organization, $invitation->membership->getKey()
        );

        $this->assertTrue($renewed->membership->invitation_expires_at->isFuture());
        $this->assertSame(
            Membership::STATUS_ACTIVE,
            $this->memberships->acceptInvitation($invitee, $renewed->plainToken)->status
        );
    }

    public function test_an_invitation_from_another_organization_is_not_found(): void
    {
        $other = $this->createOrganization();
        $otherOwner = $this->createUser('other-owner@example.test');
        $this->createMembership($otherOwner, $other, owner: true);
        $this->attachRoleByName($otherOwner, $other, 'company_owner');

        $invitee = $this->createUser('invitee@example.test');
        $invitation = $this->memberships->invite($otherOwner, $other, $invitee);

        $this->expectException(ModelNotFoundException::class);

        $this->memberships->resendInvitation($this->owner, $this->organization, $invitation->membership->getKey());
    }

    public function test_an_employee_without_the_permission_cannot_resend(): void
    {
        $employee = $this->createUser('employee@example.test');
        $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');

        $invitee = $this->createUser('invitee@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);

        $this->expectException(AuthorizationException::class);

        $this->memberships->resendInvitation($employee, $this->organization, $invitation->membership->getKey());
    }

    public function test_revoking_deletes_the_invitation_and_allows_a_new_one(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);

        $this->memberships->revokeInvitation($this->owner, $this->organization, $invitation->membership->getKey());

        $this->assertDatabaseMissing('memberships', ['id' => $invitation->membership->getKey()]);

        $again = $this->memberships->invite($this->owner, $this->organization, $invitee);
        $this->assertSame(Membership::STATUS_INVITED, $again->membership->status);
    }

    public function test_the_invitee_can_decline_but_another_account_cannot(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $stranger = $this->createUser('stranger@example.test');
        $invitation = $this->memberships->invite($this->owner, $this->organization, $invitee);

        try {
            $this->memberships->declineInvitation($stranger, $invitation->plainToken);
            $this->fail('Another account must not decline the invitation.');
        } catch (AuthorizationException) {
            $this->assertTrue(true);
        }

        $this->memberships->declineInvitation($invitee, $invitation->plainToken);

        $this->assertDatabaseMissing('memberships', ['id' => $invitation->membership->getKey()]);
    }

    public function test_pending_invitations_are_listed_for_managers_only(): void
    {
        $invitee = $this->createUser('invitee@example.test');
        $this->memberships->invite($this->owner, $this->organization, $invitee);

        $this->assertCount(1, $this->memberships->listPendingInvitations($this->owner, $this->organization));

        $outsider = $this->createUser('outsider@example.test');

        $this->expectException(AuthorizationException::class);

        $this->memberships->listPendingInvitations($outsider, $this->organization);
    }

    public function test_a_transfer_removes_the_old_membership_and_creates_a_clean_new_one(): void
    {
        $employee = $this->createUser('employee@example.test');
        $old = $this->createMembership($employee, $this->organization);
        $this->attachRoleByName($employee, $this->organization, 'bookings_employee');
        $this->memberships->updateScopes($this->owner, $old, ['flights'], []);
        $this->bindTokenTo($employee, $old);

        $customer = $this->createUser('customer@example.test');
        $booking = Booking::create([
            'reference' => 'TRW-FLT-TRANSFER',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $customer->id,
            'organization_id' => $this->organization->id,
            'assigned_membership_id' => $old->id,
            'total_cents' => 100,
        ]);

        $destination = $this->createOrganization();

        $new = $this->memberships->transfer($this->platformAdmin, $old, $destination);

        $this->assertNotSame($old->getKey(), $new->getKey());
        $this->assertSame($destination->getKey(), $new->organization_id);
        $this->assertSame(Membership::STATUS_ACTIVE, $new->status);
        $this->assertFalse($new->is_owner);
        $this->assertSame([], $new->services());
        $this->assertSame(0, DB::table('model_has_roles')
            ->where('model_id', $employee->getKey())
            ->where('organization_id', $destination->getKey())
            ->count());

        $old->refresh();
        $this->assertSame(Membership::STATUS_REMOVED, $old->status);
        $this->assertSame($this->organization->getKey(), $old->organization_id);
        $this->assertSame(0, PersonalAccessToken::query()->where('membership_id', $old->getKey())->count());
        $this->assertNull($booking->refresh()->assigned_membership_id);
    }

    public function test_a_suspended_membership_cannot_be_transferred(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization, status: Membership::STATUS_SUSPENDED);
        $destination = $this->createOrganization();

        $this->expectException(BusinessRuleException::class);

        $this->memberships->transfer($this->platformAdmin, $membership, $destination);
    }

    public function test_removal_releases_assigned_work(): void
    {
        $employee = $this->createUser('employee@example.test');
        $membership = $this->createMembership($employee, $this->organization);
        $customer = $this->createUser('customer@example.test');

        $booking = Booking::create([
            'reference' => 'TRW-FLT-RELEASE1',
            'service' => Booking::SERVICE_FLIGHT,
            'user_id' => $customer->id,
            'organization_id' => $this->organization->id,
            'assigned_membership_id' => $membership->id,
            'total_cents' => 100,
        ]);

        $this->memberships->remove($this->owner, $membership);

        $this->assertNull($booking->refresh()->assigned_membership_id);
    }
}
