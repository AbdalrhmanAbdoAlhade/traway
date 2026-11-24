<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IBAN identifies a bank account exactly like the account number does, so it
     * is stored encrypted. The encrypted cast produces a payload longer than the
     * original 34-character column, hence the text column. Existing plain values
     * are re-encoded by the backfill below.
     */
    public function up(): void
    {
        Schema::table('payout_accounts', function (Blueprint $table) {
            $table->text('iban')->nullable()->change();
        });

        foreach (DB::table('payout_accounts')->whereNotNull('iban')->cursor() as $row) {
            DB::table('payout_accounts')->where('id', $row->id)->update([
                'iban' => encrypt($row->iban, false),
            ]);
        }
    }

    public function down(): void
    {
        foreach (DB::table('payout_accounts')->whereNotNull('iban')->cursor() as $row) {
            try {
                $plain = decrypt($row->iban, false);
            } catch (Throwable) {
                continue;
            }

            DB::table('payout_accounts')->where('id', $row->id)->update(['iban' => $plain]);
        }

        Schema::table('payout_accounts', function (Blueprint $table) {
            $table->string('iban', 34)->nullable()->change();
        });
    }
};
