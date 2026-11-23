<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travelers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10)->default('adult'); // adult | child | infant
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('gender', 10)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->text('passport_number')->nullable(); // encrypted cast
            $table->string('passport_issuing_country', 2)->nullable();
            $table->date('passport_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'deleted_at']);
        });

        // Reusable saved documents (F-038, BR-050, BR-053)
        Schema::create('user_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traveler_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40); // passport | photo | national_id | ...
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size_bytes');
            $table->date('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        // BR-016: tokenized only, never PAN or CVV.
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->string('provider_token');
            $table->string('brand', 30)->nullable();
            $table->char('last4', 4);
            $table->unsignedTinyInteger('exp_month');
            $table->unsignedSmallInteger('exp_year');
            $table->boolean('is_default')->default(false);
            // = user_id while default, else NULL. Unique => one default per user (BR-017).
            $table->unsignedBigInteger('default_owner_key')->nullable()->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['provider', 'provider_token']);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('favoritable');
            $table->timestamps();

            $table->unique(['user_id', 'favoritable_type', 'favoritable_id'], 'favorites_unique');
        });

        // BR-025: versioned terms / privacy acceptance.
        Schema::create('terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30); // terms | privacy
            $table->string('version', 20);
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('accepted_at');

            $table->unique(['user_id', 'document_type', 'version'], 'terms_acceptances_unique');
        });

        // DEC-09: request -> 7-day grace -> anonymization.
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // pending | cancelled | blocked | anonymized
            $table->string('status', 20)->default('pending');
            $table->timestamp('requested_at');
            $table->timestamp('grace_ends_at');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            // upcoming_trip | visa_under_review | refund_pending
            $table->string('blocked_reason', 30)->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            // = user_id while pending, else NULL => one open request per user.
            $table->unsignedBigInteger('pending_user_key')->nullable()->unique();
            $table->timestamps();

            $table->index(['status', 'grace_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
        Schema::dropIfExists('terms_acceptances');
        Schema::dropIfExists('favorites');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('user_documents');
        Schema::dropIfExists('travelers');
    }
};
