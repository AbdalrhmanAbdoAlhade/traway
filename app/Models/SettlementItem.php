<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SettlementItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'settlement_id',
        'partner_ledger_entry_id',
        'amount_cents',
        'entry_type',
    ];

    protected $casts = [
        'amount_cents' => 'integer',
    ];

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(Settlement::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(PartnerLedgerEntry::class, 'partner_ledger_entry_id');
    }
}