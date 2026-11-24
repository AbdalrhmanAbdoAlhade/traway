<?php

use App\Models\Organization;

return [

    /*
    |--------------------------------------------------------------------------
    | Team (tenant) column
    |--------------------------------------------------------------------------
    |
    | Must match config/permission.php. Kept here so authorization services do
    | not hard-code the column name.
    |
    */
    'team_foreign_key' => 'organization_id',

    /*
    |--------------------------------------------------------------------------
    | Protected Owner templates
    |--------------------------------------------------------------------------
    |
    | These role templates are immutable: they cannot be deleted, renamed or
    | have their mandatory permissions modified by an organization owner.
    | `service_provider_owner` is the dedicated Owner template for the
    | service_provider organization type.
    |
    */
    'protected_owner_templates' => [
        'company_owner' => Organization::TYPE_COMPANY,
        'hotel_owner' => Organization::TYPE_HOTEL,
        'service_provider_owner' => Organization::TYPE_SERVICE_PROVIDER,
    ],

    /*
    |--------------------------------------------------------------------------
    | Non-delegable permissions
    |--------------------------------------------------------------------------
    |
    | An organization owner may hold these permissions but may never grant them
    | to an employee, directly or through a custom role. Every `platform.*`
    | permission is implicitly non-delegable inside a tenant.
    |
    */
    'non_delegable' => [
        'org.services.request',
        'employees.create',
        'employees.update',
        'employees.deactivate',
        'roles.manage',
        'roles.assign',
        'settlements.manage',
        'bookings.refund',
    ],

    /*
    |--------------------------------------------------------------------------
    | Owner-only structural actions
    |--------------------------------------------------------------------------
    |
    | Never expressible as a permission string on an employee membership.
    | Enforced by the organization management services and policies.
    |
    | Ownership is single-holder: the database allows exactly one active owner
    | per organization (memberships.active_owner_org_id is unique), so ownership
    | changes are always a transfer, never an addition or a removal of one owner.
    */
    'owner_only_actions' => [
        'ownership.transfer',
        'organization.close',
        'organization.delete',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission families
    |--------------------------------------------------------------------------
    */
    'permission_prefixes' => [
        'platform' => 'platform.',
        'organization' => 'org.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Service scopes
    |--------------------------------------------------------------------------
    |
    | Only these values are valid in membership_service_scopes.service.
    |
    */
    'service_scopes' => ['hotels', 'tours', 'visas'],

    // Permission namespaces are bound to one service; callers cannot spoof this mapping.
    'permission_service_prefixes' => [
        'bookings.' => 'hotels',
        'rooms.' => 'hotels',
        'rates.' => 'hotels',
        'tours.' => 'tours',
        'tour_bookings.' => 'tours',
        'visa_requests.' => 'visas',
        'visa_offerings.' => 'visas',
    ],

    /*
    |--------------------------------------------------------------------------
    | Coupons (DEC-03)
    |--------------------------------------------------------------------------
    */
    'coupon' => [
        'default_max_uses_per_customer' => 1,
        'service_scopes' => ['flights', 'hotels', 'visas'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Account deletion (DEC-09)
    |--------------------------------------------------------------------------
    */
    'account_deletion_grace_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Money (DEC-02)
    |--------------------------------------------------------------------------
    */
    'currency' => 'USD',
    'currency_minor_units' => 100,
];
