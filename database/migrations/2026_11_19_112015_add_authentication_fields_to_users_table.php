
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');

            // Contact information
            $table->string('phone', 20)->nullable()->unique();
            $table->timestamp('phone_verified_at')->nullable();

            // Personal information
            $table->string('passport_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();

            // Account status
            $table->string('status', 30)
                ->default('pending_verification')
                ->index();

            // Profile
            $table->timestamp('profile_completed_at')->nullable();

            // Marketing consent
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('marketing_consent_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone']);
            $table->dropIndex(['status']);

            $table->dropColumn([
                'first_name',
                'last_name',
                'phone',
                'phone_verified_at',
                'passport_name',
                'date_of_birth',
                'nationality',
                'status',
                'profile_completed_at',
                'marketing_consent',
                'marketing_consent_at',
            ]);
        });
    }
};
