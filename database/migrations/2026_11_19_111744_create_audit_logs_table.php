
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // User who performed the action
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('actor_type', 30);

            // admin_panel or bookings
            $table->string('module', 30);

            // create, update, delete, cancel, approve...
            $table->string('action', 50);

            $table->text('description')->nullable();

            // Polymorphic subject
            $table->nullableMorphs('subject');

            // Changes
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request information
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->string('method', 10)->nullable();
            $table->text('endpoint')->nullable();

            $table->unsignedSmallInteger('status_code')->nullable();

            // Immutable creation timestamp
            $table->timestamp('created_at')->useCurrent();

            $table->index(['module', 'action']);
            $table->index(['actor_type', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
