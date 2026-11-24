<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            // 1) internal id
            $table->id();
            // 2) customer-facing reference, random (NOT sequential), e.g. TRW-FLT-7K3M9QXP.
            //    Alphabet without 0/O/1/I; generated in the app, retried on unique violation.
            $table->string('reference', 40)->unique();
            // flight | hotel | tour | esim | visa
            $table->string('service', 20);

            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            // Owning partner (hotel / company). NULL for platform-sold products.
            $table->foreignId('organization_id')->nullable()->constrained()->restrictOnDelete();
            // Hotel property the booking belongs to. NULL for every non-hotel service.
            // Validated against organization_id / service so a booking can never
            // point at a property owned by a different tenant.
            $table->foreignId('hotel_id')->nullable()->constrained()->restrictOnDelete();
            // BR-087 / BR-115: assigned-only employees.
            $table->foreignId('assigned_membership_id')->nullable()
                ->constrained('memberships')->nullOnDelete();
            $table->foreignId('checkout_session_id')->nullable()
                ->constrained('checkout_sessions')->nullOnDelete();

            // BR-076: pending_payment | confirmed | completed | cancelled | refunded | expired
            $table->string('status', 30)->default('pending_payment');

            // 3) external provider reference (airline PNR, hotel confirmation, eSIM order id)
            $table->string('provider', 50)->nullable();
            $table->string('provider_reference', 100)->nullable()->index();

            // Money: integer cents, USD only (DEC-02). All values are snapshots.
            $table->unsignedBigInteger('subtotal_cents')->default(0);
            $table->unsignedBigInteger('taxes_cents')->default(0);
            $table->unsignedBigInteger('fees_cents')->default(0);
            $table->unsignedBigInteger('discount_cents')->default(0);
            $table->unsignedBigInteger('total_cents')->default(0);
            $table->char('currency', 3)->default('USD');

            // DEC-03: one coupon per booking; code kept as a snapshot.
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->string('coupon_code', 50)->nullable();

            // BR-096 / BR-097: commission snapshot.
            $table->unsignedSmallInteger('commission_rate_bp')->nullable();
            $table->unsignedBigInteger('commission_cents')->default(0);
            $table->unsignedBigInteger('partner_net_cents')->default(0);

            // DEC-09: contact snapshot, wiped on anonymization so partners see no personal data.
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 20)->nullable();

            $table->timestamp('expires_at')->nullable(); // pending_payment cleanup
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('anonymized_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['organization_id', 'status', 'created_at']);
            $table->index(['hotel_id', 'status']);
            $table->index(['service', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
