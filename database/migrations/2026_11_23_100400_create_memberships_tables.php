<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Platform Admin / Platform Admin Staff are memberships in the single
        // organization of type "platform". Customers have no membership.
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            // invited | active | suspended | removed
            $table->string('status', 20)->default('invited');
            $table->boolean('is_owner')->default(false);
            $table->string('job_title', 100)->nullable();

            // Exactly one ACTIVE owner per organization, enforced by the DB.
            // The service sets this to organization_id while (is_owner AND status = active), else NULL.
            $table->unsignedBigInteger('active_owner_org_id')->nullable()->unique();

            // Bumped on every role / grant / scope change; used as the permission cache key (PE-12).
            $table->unsignedInteger('permissions_version')->default(1);

            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invitation_token_hash', 64)->nullable()->unique();
            $table->timestamp('invitation_expires_at')->nullable();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('removed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'organization_id']);
            $table->index(['organization_id', 'status']);
        });

        // Service scope: flights | hotels | tours | visas | esim
        Schema::create('membership_service_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->string('service', 20);
            $table->timestamps();

            $table->unique(['membership_id', 'service']);
        });

        // Direct per-employee grants (additive). Role assignments live in Spatie's model_has_roles.
        // granted_by_* describe the CURRENT grant only. This is not a history log.
        Schema::create('membership_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')
                ->constrained(config('permission.table_names.permissions'))->cascadeOnDelete();
            $table->foreignId('granted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('granted_by_membership_id')->nullable()
                ->constrained('memberships')->nullOnDelete();
            $table->timestamps();

            $table->unique(['membership_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_permissions');
        Schema::dropIfExists('membership_service_scopes');
        Schema::dropIfExists('memberships');
    }
};
