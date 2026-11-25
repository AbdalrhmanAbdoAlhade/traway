<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->string('room_number', 20)->nullable()->after('guest_name');
            $table->timestamp('checked_in_at')->nullable()->after('room_number');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
            $table->foreignId('checked_in_by_membership_id')->nullable()->after('checked_out_at')
                ->constrained('memberships')->nullOnDelete();
            $table->foreignId('checked_out_by_membership_id')->nullable()->after('checked_in_by_membership_id')
                ->constrained('memberships')->nullOnDelete();

            $table->index(['hotel_id', 'check_out']);
        });
    }

    public function down(): void
    {
        Schema::table('hotel_bookings', function (Blueprint $table) {
            $table->dropIndex(['hotel_id', 'check_out']);
            $table->dropConstrainedForeignId('checked_in_by_membership_id');
            $table->dropConstrainedForeignId('checked_out_by_membership_id');
            $table->dropColumn(['room_number', 'checked_in_at', 'checked_out_at']);
        });
    }
};
