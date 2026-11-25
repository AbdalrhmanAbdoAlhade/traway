<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('settlement_id')
                ->constrained('settlements')
                ->cascadeOnDelete();

            // القيد المالي يخص تسوية واحدة فقط؛ ولا يمكن حذفه وهو مربوط
            $table->foreignId('partner_ledger_entry_id')
                ->constrained('partner_ledger_entries')
                ->restrictOnDelete();

            // snapshot وقت التوليد حتى لو القيد الأصلي اتغير
            $table->bigInteger('amount_cents');
            $table->string('entry_type', 50);

            $table->timestamps();

            $table->unique('partner_ledger_entry_id', 'settlement_items_ledger_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settlement_items');
    }
};