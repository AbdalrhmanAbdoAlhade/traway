<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoomRate extends Model
{
    protected $fillable = [
        'room_type_id',
        'date',
        'price_cents',
        'available_units',
        'is_stop_sell',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'price_cents' => 'integer',
            'available_units' => 'integer',
            'is_stop_sell' => 'boolean',
        ];
    }

    public function roomType(): BelongsTo
    {
        return $this->belongsTo(RoomType::class);
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
