<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupon_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->restrictOnDelete();
            // unique => one coupon per booking (DEC-03).
            $table->foreignId('booking_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('discount_cents');
            // Set once when a full refund before service delivery gives the use back.
            // Non-NULL => already released, never released twice.
            // Per-customer usage count = redemptions WHERE released_at IS NULL.
            $table->timestamp('released_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_redemptions');
    }
};
