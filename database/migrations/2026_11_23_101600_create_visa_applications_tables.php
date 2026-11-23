<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('visa_offering_id')->constrained()->restrictOnDelete();
            // Publishing company, copied from the offering (tenant scoping).
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('assigned_membership_id')->nullable()
                ->constrained('memberships')->nullOnDelete();

            // DEC-07: submitted | under_review | documents_required | processing | approved | rejected
            $table->string('status', 30)->default('submitted');

            // Applicant snapshot (wiped on anonymization).
            $table->string('applicant_name');
            $table->text('passport_number')->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->string('nationality', 100)->nullable(); // informational only, never used for pricing

            // Price snapshot (DEC-06). Fee is non-refundable on rejection.
            $table->unsignedBigInteger('price_cents');
            $table->unsignedSmallInteger('expected_days_min')->nullable();
            $table->unsignedSmallInteger('expected_days_max')->nullable();

            // Dates of the ISSUED visa, not of the offering.
            $table->date('visa_valid_from')->nullable();
            $table->date('visa_valid_until')->nullable();

            $table->text('rejection_reason')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('visa_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_offering_document_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->string('status', 20)->default('pending'); // pending | accepted | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_application_documents');
        Schema::dropIfExists('visa_applications');
    }
};
