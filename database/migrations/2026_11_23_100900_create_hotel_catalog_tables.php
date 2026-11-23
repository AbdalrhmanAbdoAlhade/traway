<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('city', 100);                       // DEC-08 search field
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();    // DEC-08
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            // draft | in_review | published | suspended (BR-110)
            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city', 'status']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->json('description')->nullable();
            $table->unsignedTinyInteger('max_adults');
            $table->unsignedTinyInteger('max_children')->default(0);
            $table->unsignedTinyInteger('max_occupancy');
            $table->unsignedSmallInteger('total_units');
            $table->unsignedBigInteger('base_price_cents');
            $table->json('amenities')->nullable();
            // BR-064 / BR-067: cancellation and pay-now terms are per room type.
            $table->string('cancellation_policy', 30)->default('non_refundable'); // free_until_deadline | non_refundable
            $table->unsignedSmallInteger('free_cancellation_hours_before')->nullable();
            $table->boolean('allows_pay_at_hotel')->default(false);
            $table->unsignedSmallInteger('pay_now_discount_bp')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // Daily availability + price per room type (BR-061, BR-066, BR-106).
        Schema::create('room_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('price_cents');
            $table->unsignedSmallInteger('available_units');
            $table->boolean('is_stop_sell')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['room_type_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_rates');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('hotels');
    }
};
