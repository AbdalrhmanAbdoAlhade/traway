<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            // NULL = platform coupon; otherwise the organization that issued it.
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            // 0 for platform coupons, organization_id otherwise. Makes (scope, code) unique,
            // so SUMMER25 can exist once per issuer.
            $table->unsignedBigInteger('scope_key')->default(0);
            $table->string('code', 50);
            $table->string('campaign_name')->nullable();
            // NULL = general; otherwise flight | hotel | tour | esim | visa
            $table->string('service_scope', 20)->nullable();
            $table->string('discount_type', 20); // percentage | fixed
            // percentage => basis points (1000 = 10%); fixed => cents
            $table->unsignedBigInteger('discount_value');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_usage')->nullable();   // total limit, NULL = unlimited
            $table->unsignedInteger('usage_count')->default(0);
            // Per customer: default 1, NULL = unlimited.
            $table->unsignedInteger('max_uses_per_customer')->nullable()->default(1);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['scope_key', 'code']);
            $table->index(['organization_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
