<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DEC-06: price by country + visa type only. NOT by applicant nationality.
        Schema::create('visa_offerings', function (Blueprint $table) {
            $table->id();
            // NULL = published by the platform; otherwise the publishing company.
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('country_code', 2)->index();
            $table->string('visa_type', 40); // tourist | business | residence ...
            $table->json('name');            // {"ar": "...", "en": "..."}
            $table->json('description')->nullable();
            $table->unsignedBigInteger('price_cents');
            // DEC-07: expected result duration, per offering.
            $table->unsignedSmallInteger('expected_days_min')->nullable();
            $table->unsignedSmallInteger('expected_days_max')->nullable();
            // Validity of the OFFERING (listing window) ...
            $table->timestamp('offered_from')->nullable();
            $table->timestamp('offered_until')->nullable();
            // ... is distinct from the validity of the ISSUED visa (stored on the application).
            $table->unsignedSmallInteger('issued_validity_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['country_code', 'visa_type', 'is_active']);
        });

        Schema::create('visa_offering_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_offering_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 40);
            $table->json('name');
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_offering_documents');
        Schema::dropIfExists('visa_offerings');
    }
};
