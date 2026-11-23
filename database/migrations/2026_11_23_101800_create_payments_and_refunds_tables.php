<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only (BR-102): no soft deletes; amount / reference never edited.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique(); // PAY-xxxxxxxx
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_method_id')->nullable()
                ->constrained('payment_methods')->nullOnDelete();
            $table->string('provider', 30);
            $table->string('provider_payment_id', 100)->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->unsignedBigInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            // pending | authorized | captured | failed | voided
            $table->string('status', 20)->default('pending');
            $table->string('failure_code', 100)->nullable();
            $table->string('card_brand', 30)->nullable();
            $table->char('card_last4', 4)->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_payment_id']);
            $table->index(['booking_id', 'status']);
        });

        // DEC-01 / DEC-10. No fee or percentage rule is stored here: cancellation fees follow the gateway.
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('booking_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            // policy_automatic | manual_exception
            $table->string('type', 20);
            // pending_approval | approved | rejected | processing | succeeded | failed
            $table->string('status', 20)->default('pending_approval');
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('gateway_fee_cents')->nullable(); // as reported by the gateway
            $table->text('reason')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete(); // NULL = system
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('provider_refund_id', 100)->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
    }
};
