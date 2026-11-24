<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank / wallet destinations a partner can be paid into. Secrets are
        // stored encrypted; partners never see platform payment credentials.
        Schema::create('payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('bank'); // bank | wallet
            $table->string('label')->nullable();
            $table->string('account_name');
            $table->text('account_number')->nullable(); // encrypted cast
            $table->string('iban', 34)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('swift_code', 11)->nullable();
            $table->string('country_code', 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_default')->default(false);
            // = organization_id while default, else NULL => one default per organization.
            $table->unsignedBigInteger('default_owner_key')->nullable()->unique();
            $table->string('status', 20)->default('pending'); // pending | verified | rejected
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
        });

        // DEC-10: on-demand payouts. Whether the gateway supports payouts is still unverified.
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            // Destination account. NULL only for legacy/manual adjustments.
            $table->foreignId('payout_account_id')->nullable()
                ->constrained('payout_accounts')->restrictOnDelete();
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
        Schema::dropIfExists('payout_accounts');
    }
};
