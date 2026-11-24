<?php

namespace Database\Seeders;

use App\Models\Membership;
use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command?->warn('SUPER_ADMIN_EMAIL / SUPER_ADMIN_PASSWORD not set. Super admin skipped.');

            return;
        }

        $platform = Organization::query()->where('type', Organization::TYPE_PLATFORM)->firstOrFail();
        $teamKey = config('permission.column_names.team_foreign_key', 'organization_id');

        DB::transaction(function () use ($email, $password, $platform, $teamKey) {
            $user = User::query()->firstOrNew(['email' => $email]);
            $user->fill([
                'name' => 'Traway Admin',
                'password' => $password,
                'status' => 'active',
            ]);
            $user->email_verified_at ??= now();
            $user->save();

            // The platform has exactly one active owner: the super admin.
            Membership::query()->updateOrCreate(
                ['user_id' => $user->id, 'organization_id' => $platform->id],
                [
                    'status' => Membership::STATUS_ACTIVE,
                    'is_owner' => true,
                    'job_title' => 'Platform Admin',
                    'accepted_at' => now(),
                ]
            );

            $role = Role::query()
                ->where('name', 'platform_super_admin')
                ->where('guard_name', 'web')
                ->where($teamKey, $platform->id)
                ->firstOrFail();

            app(PermissionRegistrar::class)->setPermissionsTeamId($platform->id);
            $user->unsetRelation('roles');
            $user->syncRoles([$role]);
        });
    }
}
