<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /*
        |--------------------------------------------------------------------------
        | Permissions
        |--------------------------------------------------------------------------
        */

        $permissions = [

            // Dashboard
            'dashboard.view',

            // Users
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.suspend',
            'users.ban',

            // Roles
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Permissions
            'permissions.view',
            'permissions.manage',

            // Bookings
            'bookings.view',
            'bookings.create',
            'bookings.update',
            'bookings.cancel',
            'bookings.assign',
            'bookings.confirm',

            // Payments
            'payments.view',
            'payments.refund',
            'payments.manage',

            // Reports
            'reports.view',
            'reports.export',

            // Content
            'content.view',
            'content.create',
            'content.update',
            'content.delete',
            'content.publish',

            // Settings
            'settings.view',
            'settings.update',

            // Audit Logs
            'audit_logs.view',
            'audit_logs.export',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        $roles = [
            'Super Admin',
            'Admin',
            'Manager',
            'Support',
            'Booking Manager',
            'Finance',
            'Content Manager',
            'User',
        ];

        foreach ($roles as $role) {
            Role::findOrCreate($role, 'web');
        }

        /*
        |--------------------------------------------------------------------------
        | Role Permissions
        |--------------------------------------------------------------------------
        */

        $rolePermissions = [

            'Admin' => [
                'dashboard.view',

                'users.view',
                'users.create',
                'users.update',
                'users.suspend',

                'roles.view',

                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.cancel',
                'bookings.assign',
                'bookings.confirm',

                'payments.view',
                'payments.refund',
                'payments.manage',

                'reports.view',
                'reports.export',

                'content.view',
                'content.create',
                'content.update',
                'content.delete',
                'content.publish',

                'settings.view',
                'settings.update',

                'audit_logs.view',
                'audit_logs.export',
            ],

            'Manager' => [
                'dashboard.view',
                'users.view',
                'users.update',

                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.assign',
                'bookings.confirm',

                'payments.view',
                'reports.view',
                'reports.export',

                'content.view',
            ],

            'Support' => [
                'dashboard.view',
                'users.view',
                'users.update',
                'bookings.view',
                'bookings.update',
            ],

            'Booking Manager' => [
                'dashboard.view',
                'bookings.view',
                'bookings.create',
                'bookings.update',
                'bookings.cancel',
                'bookings.assign',
                'bookings.confirm',
                'users.view',
            ],

            'Finance' => [
                'dashboard.view',
                'payments.view',
                'payments.refund',
                'payments.manage',
                'reports.view',
                'reports.export',
                'bookings.view',
            ],

            'Content Manager' => [
                'dashboard.view',
                'content.view',
                'content.create',
                'content.update',
                'content.delete',
                'content.publish',
            ],

            'User' => [],
        ];

        foreach ($rolePermissions as $roleName => $permissionsList) {
            $role = Role::findByName($roleName, 'web');

            $role->syncPermissions($permissionsList);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
