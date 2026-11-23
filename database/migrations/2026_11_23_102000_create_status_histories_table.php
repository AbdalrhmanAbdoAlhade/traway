<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Business status timeline for bookings, visa applications, refunds, tours, payouts.
        // This is NOT an audit log.
        Schema::create('status_histories', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('actor_membership_id')->nullable()->constrained('memberships')->nullOnDelete();
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('status_histories');
    }
};
