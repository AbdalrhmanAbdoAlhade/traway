<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DEC-10: on-demand payouts. Whether the gateway supports payouts is still unverified.
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('amount_cents');
            // requested | processing | transferred | failed | rejected  (BR-099)
            $table->string('status', 20)->default('requested');
            $table->string('method', 20)->default('manual'); // manual | gateway
            $table->string('provider_payout_id', 100)->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transferred_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // Append-only. Partner balance = SUM(amount_cents), so a negative balance
        // naturally reduces the next payout (DEC-10).
        Schema::create('partner_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->string('entry_key', 100)->unique(); // idempotency: e.g. "sale:{booking_id}"
            // sale | commission | refund | gateway_fee | chargeback | payout | adjustment
            $table->string('type', 20);
            $table->bigInteger('amount_cents'); // signed
            $table->foreignId('booking_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('payout_request_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['organization_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_ledger_entries');
        Schema::dropIfExists('payout_requests');
    }
};
