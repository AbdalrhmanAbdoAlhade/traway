<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->restrictOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('destination_city', 100);
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedSmallInteger('group_size_max')->nullable();
            $table->unsignedBigInteger('price_cents');
            $table->unsignedBigInteger('child_price_cents')->nullable();
            $table->string('cover_image')->nullable();
            $table->json('gallery')->nullable();
            // draft | in_review | published | documents_required | rejected | expired (BR-069)
            $table->string('status', 30)->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'destination_city']);
        });

        Schema::create('tour_itinerary_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('day_number');
            $table->json('title');
            $table->json('description')->nullable();

            $table->unique(['tour_id', 'day_number']);
        });

        Schema::create('tour_pickup_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
        });

        // BR-070: capacity lives on each slot.
        Schema::create('tour_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->unsignedSmallInteger('capacity');
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('status', 20)->default('open'); // open | closed | cancelled
            $table->timestamps();

            $table->index(['tour_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_slots');
        Schema::dropIfExists('tour_pickup_points');
        Schema::dropIfExists('tour_itinerary_days');
        Schema::dropIfExists('tours');
    }
};
