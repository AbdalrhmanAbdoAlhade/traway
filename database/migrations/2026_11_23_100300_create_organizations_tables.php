<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();
            // platform | company | hotel | service_provider (string, not enum, so types can grow)
            $table->string('type', 30);
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('slug')->unique();
            // pending | active | suspended | closed
            $table->string('status', 30)->default('pending');
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            // Basis points: 1000 = 10%. Applied prospectively only (BR-096).
            $table->unsignedSmallInteger('commission_rate_bp')->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'status']);
        });

        // Partner join requests (F-074 .. F-077, BR-081 .. BR-083)
        Schema::create('organization_applications', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // company | hotel
            $table->string('business_name');
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('responsible_name');
            $table->string('phone', 20);
            $table->string('email')->index();
            // submitted | under_review | approved | rejected
            $table->string('status', 30)->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reapply_after')->nullable();
            $table->timestamp('terms_accepted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            // Created on approval.
            $table->foreignId('organization_id')->nullable()->constrained()->nullOnDelete();
            // Activation link: only the hash is stored.
            $table->string('activation_token_hash', 64)->nullable()->unique();
            $table->timestamp('activation_expires_at')->nullable();
            $table->timestamp('activation_used_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        // Legal documents (BR-101). Belong to an application first, then to the organization.
        Schema::create('organization_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('application_id')->nullable()
                ->constrained('organization_applications')->cascadeOnDelete();
            // tourism_license | commercial_register | tax_card | hotel_license
            $table->string('type', 40);
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            // pending | verified | rejected | expired
            $table->string('status', 20)->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->date('expires_at')->nullable()->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_documents');
        Schema::dropIfExists('organization_applications');
        Schema::dropIfExists('organizations');
    }
};
