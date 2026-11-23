<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_packages', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50);
            $table->string('provider_package_id', 100);
            $table->string('country_code', 2);
            $table->string('name');
            $table->unsignedInteger('data_mb')->nullable();
            $table->unsignedSmallInteger('validity_days');
            $table->unsignedBigInteger('cost_cents');
            $table->unsignedSmallInteger('markup_bp')->nullable();
            // BR-008: the stored sale price is what the customer pays.
            $table->unsignedBigInteger('sale_price_cents');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_stale')->default(false); // Q-32: keep last known good
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_package_id']);
            $table->index(['country_code', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_packages');
    }
};
