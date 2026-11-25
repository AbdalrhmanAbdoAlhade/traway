<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            // pending | processing | paid | failed | cancelled
            $table->string('status', 20)->default('pending');

            $table->bigInteger('gross_amount_cents')->default(0);
            $table->bigInteger('commission_amount_cents')->default(0);
            $table->bigInteger('net_amount_cents')->default(0);
            $table->char('currency', 3)->default('USD');

            $table->unsignedInteger('entries_count')->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['organization_id', 'period_start', 'period_end'],
                'settlements_org_period_unique'
            );
            $table->index(['organization_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};