<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('trip_type', 20); // one_way | round_trip (multi_city out of scope: Q-08)
            $table->string('cabin', 20);
            $table->char('origin_iata', 3);
            $table->char('destination_iata', 3);
            $table->timestamps();
        });

        Schema::create('flight_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->string('leg', 10); // outbound | return
            $table->unsignedTinyInteger('sequence');
            $table->string('airline_code', 3);
            $table->string('flight_number', 10);
            $table->char('origin_iata', 3);
            $table->char('destination_iata', 3);
            $table->timestamp('departs_at');
            $table->timestamp('arrives_at');
            $table->timestamps();

            $table->index(['flight_booking_id', 'leg', 'sequence']);
        });

        // Passenger snapshot (independent from saved travelers). Wiped on anonymization.
        Schema::create('flight_passengers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('traveler_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->text('passport_number')->nullable(); // encrypted cast
            $table->date('passport_expires_at')->nullable();
            $table->string('ticket_number', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('hotel_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->restrictOnDelete();
            $table->foreignId('room_type_id')->constrained()->restrictOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');
            $table->unsignedTinyInteger('adults');
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('rooms')->default(1);
            $table->string('payment_timing', 20)->default('pay_now'); // pay_now | pay_at_hotel
            $table->string('guest_name')->nullable(); // wiped on anonymization
            $table->timestamps();

            $table->index(['hotel_id', 'check_in']);
        });

        Schema::create('tour_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('tour_id')->constrained()->restrictOnDelete();
            $table->foreignId('tour_slot_id')->constrained()->restrictOnDelete();
            $table->foreignId('pickup_point_id')->nullable()
                ->constrained('tour_pickup_points')->nullOnDelete();
            $table->unsignedSmallInteger('participants');
            $table->unsignedSmallInteger('child_participants')->default(0);
            $table->timestamps();
        });

        Schema::create('esim_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('esim_package_id')->constrained()->restrictOnDelete();
            $table->string('provider_order_id', 100)->nullable();
            $table->text('activation_payload')->nullable(); // encrypted LPA string (BR-058)
            $table->string('status', 20)->default('pending'); // pending | issued | activated | expired
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_orders');
        Schema::dropIfExists('tour_bookings');
        Schema::dropIfExists('hotel_bookings');
        Schema::dropIfExists('flight_passengers');
        Schema::dropIfExists('flight_segments');
        Schema::dropIfExists('flight_bookings');
    }
};
