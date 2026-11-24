<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');

        Schema::table($tables['roles'], function (Blueprint $table) {
            $table->string('family', 20)->default('organization'); // platform | organization
            $table->boolean('is_system')->default(false);   // immutable (owner, super admin)
            $table->boolean('is_template')->default(false); // copied per organization
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        });

        Schema::table($tables['permissions'], function (Blueprint $table) {
            $table->string('family', 20)->default('organization'); // platform | organization
            $table->string('domain', 40)->default('general')->index();
            $table->string('description')->nullable();
            // normal | sensitive | financial | access_control
            $table->string('sensitivity', 20)->default('normal');
            // platform_global | organization | service | assigned
            $table->string('scope_requirement', 30)->default('organization');
            // Which organization types may hold this permission, e.g. ["company","hotel"]; null = all.
            $table->json('allowed_org_types')->nullable();
            // Delegability is defined centrally in config/authorization.php, not per row.
        });
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        Schema::table($tables['permissions'], function (Blueprint $table) {
            $table->dropIndex(['domain']);
            $table->dropColumn([
                'family', 'domain', 'description', 'sensitivity',
                'scope_requirement', 'allowed_org_types',
            ]);
        });

        Schema::table($tables['roles'], function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['family', 'is_system', 'is_template', 'description']);
        });
    }
};
