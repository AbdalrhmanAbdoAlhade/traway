<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Traveler extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'nationality',
        'passport_number',
        'passport_issuing_country',
        'passport_expires_at',
    ];

    protected $hidden = [
        'passport_number',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_expires_at' => 'date',
            'passport_number' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
