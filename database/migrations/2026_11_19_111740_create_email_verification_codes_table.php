
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_verification_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('email');
            $table->string('code_hash', 255);

            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();

            $table->unsignedTinyInteger('attempts')->default(0);

            $table->timestamps();

            $table->index(['email', 'expires_at']);
            $table->index(['user_id', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_verification_codes');
    }
};
