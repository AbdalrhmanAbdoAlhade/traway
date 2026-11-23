<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // DEC-05: no fare hold. A session is a quote snapshot + re-validation record.
        // expires_at is for cleanup only, never a price promise to the customer.
        Schema::create('checkout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('service', 20);
            // open | price_changed | awaiting_payment | completed | abandoned
            $table->string('status', 20)->default('open');
            $table->string('idempotency_key', 100)->unique(); // BR-020
            $table->json('quote');                             // snapshot of what was quoted
            $table->unsignedBigInteger('quoted_total_cents');
            $table->unsignedBigInteger('revalidated_total_cents')->nullable();
            $table->timestamp('price_changed_at')->nullable();
            // Open question: whether the customer must accept the new price before paying.
            $table->timestamp('price_change_accepted_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('checkout_sessions');
    }
};
