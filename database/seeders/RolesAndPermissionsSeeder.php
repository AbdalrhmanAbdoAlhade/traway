<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    private const GUARD = 'web';

    public function run(): void
    {
        if (! config('permission.teams')) {
            throw new RuntimeException("Set 'teams' => true in config/permission.php before seeding.");
        }

        $platform = Organization::query()->where('type', Organization::TYPE_PLATFORM)->first();

        if (! $platform) {
            throw new RuntimeException('Platform organization missing. Run PlatformBootstrapSeeder first.');
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        DB::transaction(function () use ($platform) {
            $this->seedPermissions();
            $this->seedPlatformRoles($platform);
            $this->seedTemplateRoles();
        });

        $registrar->forgetCachedPermissions();
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    private function seedPermissions(): void
    {
        foreach ($this->catalog() as [$key, $domain, $sensitivity, $description, $types]) {
            $isPlatform = str_starts_with($key, 'platform.');

            Permission::query()->updateOrCreate(
                ['name' => $key, 'guard_name' => self::GUARD],
                [
                    'family' => $isPlatform ? Permission::FAMILY_PLATFORM : Permission::FAMILY_ORGANIZATION,
                    'domain' => $domain,
                    'description' => $description,
                    'sensitivity' => $sensitivity,
                    'scope_requirement' => $this->scopeRequirement($key, $isPlatform),
                    'allowed_org_types' => $types,
                    // Delegability lives in config/authorization.php only.
                ]
            );
        }
    }

    private function scopeRequirement(string $key, bool $isPlatform): string
    {
        if ($isPlatform) {
            return 'platform_global';
        }

        $serviceScoped = ['tours.', 'visa_requests.', 'visa_offerings.', 'rooms.', 'rates.'];

        foreach ($serviceScoped as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return str_starts_with($key, 'rooms.') || str_starts_with($key, 'rates.')
                    ? 'property'
                    : 'service';
            }
        }

        return 'organization';
    }

    /**
     * [key, domain, sensitivity, description, allowed organization types (null = platform)]
     *
     * @return array<int, array{0:string,1:string,2:string,3:string,4:?array<int,string>}>
     */
    private function catalog(): array
    {
        // Organization types that may hold the structural (non-service) permissions.
        $all = ['company', 'hotel', 'service_provider'];
        $co = ['company'];
        $ho = ['hotel'];

        return [
            // ---------------- Platform family ----------------
            ['platform.overview.view', 'overview', 'normal', 'View platform KPIs, charts and pending queue', null],
            ['platform.notifications.view', 'notifications', 'normal', 'View platform notification inbox', null],
            ['platform.notifications.manage', 'notifications', 'normal', 'Mark notifications read', null],
            ['platform.bookings.view', 'bookings', 'normal', 'View bookings across all tenants', null],
            ['platform.bookings.export', 'bookings', 'sensitive', 'Export the all-bookings table', null],
            ['platform.partners.view', 'partners', 'normal', 'View partner directory and details', null],
            ['platform.partners.create', 'partners', 'sensitive', 'Create a partner and invite its owner', null],
            ['platform.partners.update', 'partners', 'sensitive', 'Edit partner commission, services and data', null],
            ['platform.partners.suspend', 'partners', 'sensitive', 'Suspend or reinstate a partner', null],
            ['platform.partner_requests.view', 'partners', 'normal', 'View join requests', null],
            ['platform.partner_requests.review', 'partners', 'sensitive', 'Accept or reject join requests and verify documents', null],
            ['platform.visa_catalog.view', 'catalogue', 'normal', 'View visa offerings, fees and documents', null],
            ['platform.visa_catalog.manage', 'catalogue', 'sensitive', 'Create, update and deactivate visa offerings', null],
            // Visa applications are reviewed by Traway staff only (DEC-07).
            ['platform.visa_requests.view', 'visas', 'sensitive', 'View all visa applications and uploaded documents', null],
            ['platform.visa_requests.review', 'visas', 'sensitive', 'Review visa documents and request additional documents', null],
            ['platform.visa_requests.assign', 'visas', 'sensitive', 'Assign visa applications to platform staff', null],
            ['platform.visa_requests.decide', 'visas', 'sensitive', 'Final approval or rejection of a visa application', null],
            ['platform.esim_catalog.view', 'catalogue', 'normal', 'View eSIM packages, costs and margins', null],
            ['platform.esim_catalog.manage', 'catalogue', 'sensitive', 'Create and update eSIM packages', null],
            ['platform.esim_catalog.sync', 'catalogue', 'sensitive', 'Trigger eSIM provider sync', null],
            ['platform.customers.view', 'customers', 'sensitive', 'View customer directory and profiles', null],
            ['platform.customers.update', 'customers', 'sensitive', 'Edit customer profile data', null],
            ['platform.customers.block', 'customers', 'sensitive', 'Block or unblock a customer account', null],
            ['platform.customers.export', 'customers', 'sensitive', 'Export customer data', null],
            ['platform.deletion_requests.view', 'customers', 'sensitive', 'View account-deletion requests', null],
            ['platform.deletion_requests.process', 'customers', 'sensitive', 'Resolve blocked deletion requests', null],
            ['platform.finance.view', 'finance', 'financial', 'View payments, commissions, dues and transactions', null],
            ['platform.finance.settlements.execute', 'finance', 'financial', 'Execute or mark payout transfers', null],
            ['platform.finance.export', 'finance', 'financial', 'Export financial data', null],
            ['platform.roles.view', 'access', 'normal', 'View platform roles and permissions', null],
            ['platform.roles.manage', 'access', 'access_control', 'Create, edit and delete platform roles', null],
            ['platform.roles.assign', 'access', 'access_control', 'Assign platform roles to staff', null],
            ['platform.coupons.view', 'coupons', 'normal', 'View platform-wide coupons', null],
            ['platform.coupons.manage', 'coupons', 'sensitive', 'Create, edit and deactivate platform coupons', null],
            ['platform.organization_access.view', 'access', 'sensitive', 'View memberships, roles and grants of any organization', null],
            ['platform.organization_access.manage', 'access', 'access_control', 'Assign roles and permissions in any organization', null],

            // ---------------- Organization family: structural ----------------
            ['org.profile.view', 'profile', 'normal', 'View organization profile and settings', $all],
            ['org.profile.update', 'profile', 'sensitive', 'Edit organization profile and settings', $all],
            ['org.profile.publish', 'profile', 'sensitive', 'Publish the hotel profile to customers', $ho],
            ['org.documents.manage', 'profile', 'sensitive', 'Upload or replace legal documents', $all],
            ['org.notifications.view', 'notifications', 'normal', 'View organization notification inbox', $all],

            ['employees.view', 'access', 'normal', 'View employees and their roles', $all],
            ['employees.create', 'access', 'access_control', 'Invite an employee', $all],
            ['employees.update', 'access', 'access_control', 'Edit employee profile, role and service scope', $all],
            ['employees.deactivate', 'access', 'access_control', 'Suspend or remove an employee', $all],
            ['roles.view', 'access', 'normal', 'View roles and their permissions', $all],
            ['roles.manage', 'access', 'access_control', 'Create, edit and delete custom roles', $all],
            ['roles.assign', 'access', 'access_control', 'Assign roles to employees', $all],

            ['reports.view', 'reports', 'sensitive', 'View operational and financial reports', $all],
            ['reports.export', 'reports', 'sensitive', 'Export reports', $all],
            ['settlements.view', 'settlements', 'financial', 'View settlements and dues', $all],
            ['settlements.manage', 'settlements', 'financial', 'Request an on-demand payout', $all],

            // ---------------- Organization family: bookings ----------------
            ['bookings.view', 'bookings', 'sensitive', 'View bookings and guest data', ['company', 'hotel']],
            ['bookings.create', 'bookings', 'sensitive', 'Create a booking on behalf of a customer', ['company', 'hotel']],
            ['bookings.update', 'bookings', 'sensitive', 'Modify booking details', ['company', 'hotel']],
            ['bookings.cancel', 'bookings', 'sensitive', 'Cancel a booking', ['company', 'hotel']],
            ['bookings.refund', 'bookings', 'financial', 'Initiate an eligible refund', ['company', 'hotel']],
            ['bookings.checkin', 'bookings', 'normal', 'Check a guest in', $ho],
            ['bookings.checkout', 'bookings', 'normal', 'Check a guest out', $ho],
            ['bookings.export', 'bookings', 'sensitive', 'Export booking lists', ['company', 'hotel']],

            // ---------------- Organization family: tours ----------------
            ['tours.view', 'tours', 'normal', 'View tours and programmes', $co],
            ['tours.create', 'tours', 'sensitive', 'Create a tour', $co],
            ['tours.update', 'tours', 'sensitive', 'Edit a tour', $co],
            ['tours.delete', 'tours', 'sensitive', 'Delete or withdraw a tour', $co],
            ['tours.submit', 'tours', 'sensitive', 'Submit a tour for platform review', $co],
            ['tours.slots.manage', 'tours', 'sensitive', 'Manage departure slots and seat capacity', $co],

            // ---------------- Organization family: visas ----------------
            // Company staff may view applications and validate documents.
            // Final approval/rejection belongs to platform staff (DEC-07).
            ['visa_requests.view', 'visas', 'sensitive', 'View own visa applications and documents', $co],
            ['visa_requests.review', 'visas', 'sensitive', 'Validate visa documents and flag missing items', $co],
            ['visa_offerings.view', 'visas', 'normal', "View the company's visa offerings", $co],
            ['visa_offerings.manage', 'visas', 'sensitive', 'Create and update own visa offerings and prices', $co],

            // ---------------- Organization family: hotel operations ----------------
            ['rooms.view', 'rooms', 'normal', 'View room types and availability', $ho],
            ['rooms.manage', 'rooms', 'sensitive', 'Create, edit and delete room types', $ho],
            ['rates.view', 'rooms', 'normal', 'View the rate calendar', $ho],
            ['rates.update', 'rooms', 'sensitive', 'Update nightly prices and bulk adjustments', $ho],
            ['rates.stop_sell', 'rooms', 'sensitive', 'Apply or lift stop-sell', $ho],

            // ---------------- Organization family: coupons ----------------
            ['coupons.view', 'coupons', 'normal', "View the company's coupons", $co],
            ['coupons.create', 'coupons', 'sensitive', 'Create company coupons', $co],
            ['coupons.manage', 'coupons', 'sensitive', 'Edit, limit and deactivate own coupons', $co],

            // ---------------- Organization family: misc ----------------
            ['team_activity.view', 'reports', 'normal', 'View the team activity feed', $co],
            ['customers.view', 'customers', 'sensitive', 'View customer contact for own bookings', ['company', 'hotel']],
        ];
    }

    /**
     * Every organization permission that an organization of the given type may hold.
     *
     * @return array<int, string>
     */
    private function organizationKeysFor(string $type): array
    {
        return collect($this->catalog())
            ->filter(fn (array $row) => ! str_starts_with($row[0], 'platform.')
                && in_array($type, $row[4] ?? [], true))
            ->pluck(0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function platformKeys(): array
    {
        return collect($this->catalog())
            ->filter(fn (array $row) => str_starts_with($row[0], 'platform.'))
            ->pluck(0)
            ->values()
            ->all();
    }

    /*
    |--------------------------------------------------------------------------
    | Platform roles (belong to the platform organization / team)
    |--------------------------------------------------------------------------
    */

    private function seedPlatformRoles(Organization $platform): void
    {
        $roles = [
            'platform_super_admin' => [
                'All platform permissions. Contains no organization permission.',
                $this->platformKeys(),
            ],
            'platform_finance' => [
                'Payments, commissions, settlements and financial exports.',
                [
                    'platform.overview.view', 'platform.notifications.view', 'platform.notifications.manage',
                    'platform.bookings.view', 'platform.bookings.export', 'platform.partners.view',
                    'platform.esim_catalog.view', 'platform.customers.view',
                    'platform.finance.view', 'platform.finance.settlements.execute', 'platform.finance.export',
                ],
            ],
            'platform_content' => [
                'Visa and eSIM catalogues and platform coupons.',
                [
                    'platform.overview.view', 'platform.notifications.view', 'platform.notifications.manage',
                    'platform.visa_catalog.view', 'platform.visa_catalog.manage',
                    'platform.esim_catalog.view', 'platform.esim_catalog.manage', 'platform.esim_catalog.sync',
                    'platform.coupons.view', 'platform.coupons.manage',
                ],
            ],
            'platform_support' => [
                'Read and assist: customers, bookings, deletion requests and visa document intake.',
                [
                    'platform.overview.view', 'platform.notifications.view', 'platform.notifications.manage',
                    'platform.bookings.view', 'platform.partners.view', 'platform.partner_requests.view',
                    'platform.visa_catalog.view', 'platform.visa_requests.view', 'platform.visa_requests.review',
                    'platform.visa_requests.assign', 'platform.customers.view',
                    'platform.deletion_requests.view', 'platform.deletion_requests.process',
                ],
            ],
            'platform_visa_officer' => [
                'Reviews visa applications and records the final decision.',
                [
                    'platform.overview.view', 'platform.notifications.view', 'platform.notifications.manage',
                    'platform.visa_catalog.view', 'platform.visa_requests.view', 'platform.visa_requests.review',
                    'platform.visa_requests.assign', 'platform.visa_requests.decide',
                    'platform.customers.view',
                ],
            ],
        ];

        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

        foreach ($roles as $name => [$description, $keys]) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD, $teamKey => $platform->id],
                [
                    'family' => Role::FAMILY_PLATFORM,
                    'is_system' => true,
                    'is_template' => false,
                    'description' => $description,
                ]
            );

            $role->syncPermissions($keys);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Organization role templates (team = NULL)
    |--------------------------------------------------------------------------
    | Copied into an organization when it is created, so an owner can edit the
    | copy without affecting any other tenant. Owner templates are immutable and
    | must never be deleted or reduced.
    */

    private function seedTemplateRoles(): void
    {
        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

        $templates = [
            // name => [description, is_system, keys]
            'company_owner' => [
                'Company owner: every company permission. Protected template.',
                true,
                $this->organizationKeysFor(Organization::TYPE_COMPANY),
            ],
            'hotel_owner' => [
                'Hotel owner: every hotel permission. Protected template.',
                true,
                $this->organizationKeysFor(Organization::TYPE_HOTEL),
            ],
            'service_provider_owner' => [
                'Service provider owner: structural permissions only until the provider service catalogue is approved. Protected template.',
                true,
                $this->organizationKeysFor(Organization::TYPE_SERVICE_PROVIDER),
            ],

            'bookings_employee' => ['Company bookings and customer contact.', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel', 'bookings.export',
                'customers.view',
            ]],
            'visa_employee' => ['Company visa queue: document review only, no final decisions.', false, [
                'org.profile.view', 'org.notifications.view',
                'visa_requests.view', 'visa_requests.review',
                'visa_offerings.view', 'customers.view',
            ]],
            'accountant' => ['Company reports and settlements (read).', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'reports.view', 'reports.export', 'settlements.view',
            ]],
            'content_manager' => ['Company tours.', false, [
                'org.profile.view', 'org.notifications.view',
                'tours.view', 'tours.create', 'tours.update', 'tours.delete', 'tours.submit', 'tours.slots.manage',
                'visa_offerings.view',
            ]],
            'restricted_employee' => ['Assigned visa requests and read-only bookings.', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'customers.view',
                'visa_requests.view', 'visa_requests.review',
            ]],

            'reception' => ['Hotel arrivals, check-in and check-out.', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'bookings.update', 'bookings.checkin', 'bookings.checkout',
                'rooms.view', 'rates.view', 'customers.view',
            ]],
            'hotel_bookings_manager' => ['Hotel bookings, rooms and rates.', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'bookings.create', 'bookings.update', 'bookings.cancel', 'bookings.refund',
                'bookings.checkin', 'bookings.checkout', 'bookings.export',
                'rooms.view', 'rooms.manage', 'rates.view', 'rates.update', 'rates.stop_sell',
                'reports.view', 'reports.export', 'customers.view',
            ]],
            'hotel_accountant' => ['Hotel reports and settlements (read).', false, [
                'org.profile.view', 'org.notifications.view',
                'bookings.view', 'reports.view', 'reports.export', 'settlements.view',
            ]],
        ];

        foreach ($templates as $name => [$description, $isSystem, $keys]) {
            $role = Role::query()->updateOrCreate(
                ['name' => $name, 'guard_name' => self::GUARD, $teamKey => null],
                [
                    'family' => Role::FAMILY_ORGANIZATION,
                    'is_system' => $isSystem,
                    'is_template' => true,
                    'description' => $description,
                ]
            );

            $role->syncPermissions($keys);
        }
    }
}
