<?php

use App\Models\Permission;
use App\Models\Role;
use Spatie\Permission\DefaultTeamResolver;

return [

    'models' => [
        /*
         * Traway extends both package models so that organization scoping and the
         * permission catalogue metadata live on first-class application models.
         */
        'permission' => Permission::class,
        'role' => Role::class,

        /*
         * When using the "Teams" feature from this package, we need to know which
         * Eloquent model should be used to retrieve your teams.
         */
        'team' => null,

        /*
         * When using the "HasModels" trait and passing raw IDs to syncModels,
         * attachModels, or detachModels, this model class will be used to
         * resolve those IDs. If null, defaults to the guard's model.
         */
        'default_model' => null,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null, // default 'role_id',
        'permission_pivot_key' => null, // default 'permission_id',

        'model_morph_key' => 'model_id',

        /*
         * Tenant column. Every role and every role assignment is scoped to the
         * organization it belongs to (DEC-11: Spatie Teams keyed by organization_id).
         */
        'team_foreign_key' => 'organization_id',
    ],

    /*
     * When set to true, the method for checking permissions will be registered on the gate.
     * Set this to false if you want to implement custom logic for checking permissions.
     */
    'register_permission_check_method' => true,

    'register_octane_reset_listener' => false,

    'events_enabled' => false,

    /*
     * Teams Feature: required by the Traway authorization model. The package
     * migration also reads this flag, so it must stay true.
     */
    'teams' => true,

            /*
         * The class used to resolve the permissions team id when Spatie's own
         * team API is used. Tenant-aware decisions go through App\Services\
         * Authorization\AuthorizationService, which passes the organization
         * explicitly and never relies on ambient team state.
         */
        'team_resolver' => DefaultTeamResolver::class,

    'use_passport_client_credentials' => false,

    'display_permission_in_exception' => false,

    'display_role_in_exception' => false,

    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'store' => 'default',
    ],
];
