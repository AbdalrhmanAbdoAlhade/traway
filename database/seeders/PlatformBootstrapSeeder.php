<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // The single platform organization. Its id is the Spatie "team" for platform staff.
        DB::table('organizations')->updateOrInsert(
            ['id' => 1],
            [
                'type' => 'platform',
                'name' => 'Traway',
                'slug' => 'traway',
                'status' => 'active',
                'approved_at' => $now,
                'commission_rate_bp' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Default commission in basis points (1000 = 10%).
        $settings = [
            'commission.default.company' => 1000,
            'commission.default.hotel' => 1500,
        ];

        foreach ($settings as $key => $value) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => json_encode($value),
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
