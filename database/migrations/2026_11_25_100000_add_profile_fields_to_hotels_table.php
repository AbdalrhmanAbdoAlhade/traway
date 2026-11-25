
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->string('reception_phone', 30)->nullable()->after('address');
            $table->string('contact_email')->nullable()->after('reception_phone');
            $table->json('amenities')->nullable()->after('description');
            // Customer-facing cancellation policy text, translated {"ar": "...", "en": "..."}.
            $table->json('cancellation_policy')->nullable()->after('amenities');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn(['reception_phone', 'contact_email', 'amenities', 'cancellation_policy']);
        });
    }
};
