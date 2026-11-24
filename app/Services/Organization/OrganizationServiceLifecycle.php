<?php

namespace App\Services\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationService as OrganizationServiceRecord;
use App\Models\StatusHistory;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class OrganizationServiceLifecycle
{
    public function __construct(private readonly AuthorizationService $authz) {}

    public function request(User $actor, Organization $organization, string $service): OrganizationServiceRecord
    {
        $this->assertSupportedOrganization($organization, $service);

        if (! $this->authz->allows($actor, 'org.services.request', $organization)) {
            throw new AuthorizationException('Only the organization owner may request a service.');
        }

        return DB::transaction(function () use ($actor, $organization, $service) {
            Organization::query()->lockForUpdate()->findOrFail($organization->getKey());

            if (OrganizationServiceRecord::query()
                ->where('organization_id', $organization->getKey())
                ->where('service', $service)
                ->exists()) {
                throw new BusinessRuleException('A service request or service record already exists.');
            }

            $record = OrganizationServiceRecord::create([
                'organization_id' => $organization->getKey(),
                'service' => $service,
                'status' => OrganizationServiceRecord::STATUS_REQUESTED,
                'requested_by_user_id' => $actor->getKey(),
                'requested_at' => now(),
            ]);

            $this->recordTransition($record, null, OrganizationServiceRecord::STATUS_REQUESTED, $actor, $organization);

            return $record;
        });
    }

    public function approve(User $actor, OrganizationServiceRecord $service): OrganizationServiceRecord
    {
        return $this->platformTransition(
            $actor,
            $service,
            [OrganizationServiceRecord::STATUS_REQUESTED],
            OrganizationServiceRecord::STATUS_ENABLED,
            function (OrganizationServiceRecord $record) {
                $record->reviewed_at = now();
                $record->enabled_at = now();
                $record->effective_at = null;
                $record->disabled_at = null;
                $record->rejection_reason = null;
            }
        );
    }

    public function reject(User $actor, OrganizationServiceRecord $service, string $reason): OrganizationServiceRecord
    {
        if (trim($reason) === '') {
            throw new BusinessRuleException('A rejection reason is required.');
        }

        return $this->platformTransition(
            $actor,
            $service,
            [OrganizationServiceRecord::STATUS_REQUESTED],
            OrganizationServiceRecord::STATUS_REJECTED,
            function (OrganizationServiceRecord $record) use ($reason) {
                $record->reviewed_at = now();
                $record->rejection_reason = trim($reason);
            }
        );
    }

    public function suspend(User $actor, OrganizationServiceRecord $service, string $reason): OrganizationServiceRecord
    {
        if (trim($reason) === '') {
            throw new BusinessRuleException('A suspension reason is required.');
        }

        return $this->platformTransition(
            $actor,
            $service,
            [OrganizationServiceRecord::STATUS_ENABLED],
            OrganizationServiceRecord::STATUS_SUSPENDED,
            function (OrganizationServiceRecord $record) {
                $record->effective_at = now();
            },
            trim($reason)
        );
    }

    public function reactivate(User $actor, OrganizationServiceRecord $service): OrganizationServiceRecord
    {
        return $this->platformTransition(
            $actor,
            $service,
            [OrganizationServiceRecord::STATUS_SUSPENDED],
            OrganizationServiceRecord::STATUS_ENABLED,
            function (OrganizationServiceRecord $record) {
                $record->enabled_at = now();
                $record->effective_at = null;
            }
        );
    }

    public function disable(User $actor, OrganizationServiceRecord $service, string $reason): OrganizationServiceRecord
    {
        if (trim($reason) === '') {
            throw new BusinessRuleException('A disable reason is required.');
        }

        return $this->platformTransition(
            $actor,
            $service,
            [OrganizationServiceRecord::STATUS_ENABLED, OrganizationServiceRecord::STATUS_SUSPENDED],
            OrganizationServiceRecord::STATUS_DISABLED,
            function (OrganizationServiceRecord $record) {
                $record->effective_at = now();
                $record->disabled_at = now();
            },
            trim($reason)
        );
    }

    private function platformTransition(
        User $actor,
        OrganizationServiceRecord $service,
        array $allowedFrom,
        string $to,
        callable $mutate,
        ?string $note = null,
    ): OrganizationServiceRecord {
        $this->assertPlatformActor($actor);

        return DB::transaction(function () use ($actor, $service, $allowedFrom, $to, $mutate, $note) {
            $record = OrganizationServiceRecord::query()
                ->with('organization')
                ->lockForUpdate()
                ->findOrFail($service->getKey());

            if (! in_array($record->status, $allowedFrom, true)) {
                throw new BusinessRuleException('Invalid organization service state transition.');
            }

            $from = $record->status;
            $record->status = $to;
            $record->reviewed_by_user_id = $actor->getKey();
            $mutate($record);
            $record->save();

            $this->recordTransition($record, $from, $to, $actor, $record->organization, $note);

            return $record->refresh();
        });
    }

    private function assertPlatformActor(User $actor): void
    {
        $platform = Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();

        if ($platform === null || ! $this->authz->allows($actor, 'platform.partners.update', $platform)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }

    private function assertSupportedOrganization(Organization $organization, string $service): void
    {
        if ($organization->isPlatform()) {
            throw new BusinessRuleException('Platform organization cannot request partner services.');
        }

        if (! in_array($service, OrganizationServiceRecord::ALLOWED_SERVICES, true)) {
            throw new BusinessRuleException('Unsupported organization service.');
        }
    }

    private function recordTransition(
        OrganizationServiceRecord $service,
        ?string $from,
        string $to,
        User $actor,
        Organization $organization,
        ?string $note = null,
    ): void {
        $platform = $organization->isPlatform()
            ? $organization
            : Organization::query()->where('type', Organization::TYPE_PLATFORM)->first();

        $actorMembership = $platform
            ? Membership::query()
                ->where('organization_id', $platform->getKey())
                ->where('user_id', $actor->getKey())
                ->where('status', Membership::STATUS_ACTIVE)
                ->first()
            : Membership::query()
                ->where('organization_id', $organization->getKey())
                ->where('user_id', $actor->getKey())
                ->where('status', Membership::STATUS_ACTIVE)
                ->first();

        StatusHistory::create([
            'subject_type' => $service->getMorphClass(),
            'subject_id' => $service->getKey(),
            'from_status' => $from,
            'to_status' => $to,
            'actor_user_id' => $actor->getKey(),
            'actor_membership_id' => $actorMembership?->getKey(),
            'note' => $note,
        ]);
    }
}
