<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hotel property scope: a hotel employee may be restricted to one or more
        // properties owned by their organization. No rows = every property the
        // organization owns (still subject to the permission itself).
        Schema::create('membership_hotel_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['membership_id', 'hotel_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_hotel_scopes');
    }
};
