<?php

namespace App\Services\Organization;

use App\Exceptions\BusinessRuleException;
use App\Models\Membership;
use App\Models\Organization;
use App\Models\OrganizationApplication;
use App\Models\User;
use App\Services\Authorization\AuthorizationService;
use App\Services\Authorization\TenantResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Partner join requests (company / hotel / service provider).
 *
 * submit()  -> partner files a request (no account needed).
 * approve() -> platform staff approve; the organization is created (pending) and
 *              a one-time activation token is generated. Only its hash is stored.
 * activate()-> the partner opens the activation link, an account is created if
 *              needed, the owner membership is activated and the organization
 *              becomes active. This is the only place an organization gets an owner.
 */
class OrganizationApplicationService
{
    private const ACTIVATION_TTL_DAYS = 14;

    public function __construct(
        private readonly AuthorizationService $authz,
        private readonly TenantResolver $tenants,
        private readonly OrganizationService $organizations,
        private readonly RoleTemplateService $templates,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function submit(array $attributes): OrganizationApplication
    {
        $type = $attributes['type'] ?? null;

        if (! in_array($type, OrganizationService::CREATABLE_TYPES, true)) {
            throw new BusinessRuleException('Unsupported organization type.');
        }

        if (empty($attributes['terms_accepted'])) {
            throw new BusinessRuleException('The terms must be accepted before applying.');
        }

        $email = trim((string) ($attributes['email'] ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new BusinessRuleException('A valid contact email is required.');
        }

        $pending = OrganizationApplication::query()
            ->where('email', $email)
            ->where('type', $type)
            ->whereIn('status', [
                OrganizationApplication::STATUS_SUBMITTED,
                OrganizationApplication::STATUS_UNDER_REVIEW,
            ])
            ->exists();

        if ($pending) {
            throw new BusinessRuleException('A request for this organization type is already under review.');
        }

        return OrganizationApplication::create([
            'type' => $type,
            'business_name' => $attributes['business_name'],
            'country_code' => $attributes['country_code'] ?? null,
            'city' => $attributes['city'] ?? null,
            'responsible_name' => $attributes['responsible_name'],
            'phone' => $attributes['phone'],
            'email' => $email,
            'status' => OrganizationApplication::STATUS_SUBMITTED,
            'terms_accepted_at' => now(),
        ]);
    }

    /**
     * @return array{organization: Organization, activation_token: string}
     */
    public function approve(User $actor, OrganizationApplication $application): array
    {
        $this->assertPlatform($actor, 'platform.partner_requests.review');

        if (! in_array($application->status, [
            OrganizationApplication::STATUS_SUBMITTED,
            OrganizationApplication::STATUS_UNDER_REVIEW,
        ], true)) {
            throw new BusinessRuleException('Only a submitted or under-review request can be approved.');
        }

        $token = Str::random(64);

        $organization = DB::transaction(function () use ($actor, $application, $token) {
            $organization = $this->organizations->createRecord([
                'type' => $application->type,
                'name' => $application->business_name,
                'legal_name' => $application->business_name,
                'country_code' => $application->country_code,
                'city' => $application->city,
                'phone' => $application->phone,
                'email' => $application->email,
                'status' => Organization::STATUS_PENDING,
            ]);

            $application->forceFill([
                'status' => OrganizationApplication::STATUS_APPROVED,
                'organization_id' => $organization->getKey(),
                'reviewed_by' => $actor->getKey(),
                'reviewed_at' => now(),
                'rejection_reason' => null,
                'activation_token_hash' => hash('sha256', $token),
                'activation_expires_at' => now()->addDays(self::ACTIVATION_TTL_DAYS),
                'activation_used_at' => null,
            ])->save();

            return $organization;
        });

        return ['organization' => $organization, 'activation_token' => $token];
    }

    public function reject(User $actor, OrganizationApplication $application, string $reason): OrganizationApplication
    {
        $this->assertPlatform($actor, 'platform.partner_requests.review');

        if (in_array($application->status, [
            OrganizationApplication::STATUS_APPROVED,
            OrganizationApplication::STATUS_REJECTED,
        ], true)) {
            throw new BusinessRuleException('This request has already been decided.');
        }

        $application->forceFill([
            'status' => OrganizationApplication::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_by' => $actor->getKey(),
            'reviewed_at' => now(),
            'activation_token_hash' => null,
            'activation_expires_at' => null,
        ])->save();

        return $application->refresh();
    }

    /**
     * Completes the activation link and creates the owner membership.
     *
     * @param  array{name?: string, password?: string}  $owner
     */
    public function activate(string $plainToken, array $owner = []): Membership
    {
        $application = OrganizationApplication::query()
            ->where('activation_token_hash', hash('sha256', $plainToken))
            ->first();

        if ($application === null || $application->status !== OrganizationApplication::STATUS_APPROVED) {
            throw new BusinessRuleException('This activation link is invalid.');
        }

        if ($application->activation_used_at !== null) {
            throw new BusinessRuleException('This activation link has already been used.');
        }

        if ($application->activation_expires_at === null || $application->activation_expires_at->isPast()) {
            throw new BusinessRuleException('This activation link has expired.');
        }

        $organization = $application->organization;

        if ($organization === null || $organization->status !== Organization::STATUS_PENDING) {
            throw new BusinessRuleException('This organization cannot be activated.');
        }

        return DB::transaction(function () use ($application, $organization, $owner) {
            $user = User::query()->where('email', $application->email)->first();

            if ($user === null) {
                $name = trim((string) ($owner['name'] ?? $application->responsible_name));
                $password = (string) ($owner['password'] ?? '');

                if ($password === '') {
                    throw new BusinessRuleException('A password is required to activate this organization.');
                }

                $user = User::create([
                    'name' => $name !== '' ? $name : $application->responsible_name,
                    'email' => $application->email,
                    'password' => Hash::make($password),
                    'status' => 'active',
                ]);
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            if (! $user->isActive()) {
                throw new AuthorizationException('This account is not active.');
            }

            $alreadyOwned = Membership::query()
                ->where('organization_id', $organization->getKey())
                ->where('status', Membership::STATUS_ACTIVE)
                ->where('is_owner', true)
                ->where('user_id', '!=', $user->getKey())
                ->exists();

            if ($alreadyOwned) {
                throw new BusinessRuleException('This organization already has an active owner.');
            }

            $membership = Membership::query()->updateOrCreate(
                [
                    'user_id' => $user->getKey(),
                    'organization_id' => $organization->getKey(),
                ],
                [
                    'status' => Membership::STATUS_ACTIVE,
                    'is_owner' => true,
                    'job_title' => 'Owner',
                    'accepted_at' => now(),
                    'invitation_token_hash' => null,
                    'invitation_expires_at' => null,
                ]
            );

            $ownerRole = $this->templates->ownerRole($organization);

            if ($ownerRole !== null) {
                DB::table(config('permission.table_names.model_has_roles'))->insertOrIgnore([
                    (config('permission.column_names.role_pivot_key') ?? 'role_id') => $ownerRole->getKey(),
                    'model_type' => $user->getMorphClass(),
                    config('permission.column_names.model_morph_key', 'model_id') => $user->getKey(),
                    config('permission.column_names.team_foreign_key', 'organization_id') => $organization->getKey(),
                ]);
            }

            $organization->forceFill([
                'status' => Organization::STATUS_ACTIVE,
                'approved_at' => $application->reviewed_at ?? now(),
                'approved_by' => $application->reviewed_by,
            ])->save();

            $application->forceFill([
                'activation_used_at' => now(),
                'activation_token_hash' => null,
            ])->save();

            $this->authz->invalidate($membership);
            $this->tenants->forget($user);

            return $membership->refresh();
        });
    }

    private function assertPlatform(User $actor, string $permission): void
    {
        $platform = Organization::query()
            ->where('type', Organization::TYPE_PLATFORM)
            ->where('status', Organization::STATUS_ACTIVE)
            ->first();

        if ($platform === null || ! $this->authz->allows($actor, $permission, $platform)) {
            throw new AuthorizationException('This action is unauthorized.');
        }
    }
}
