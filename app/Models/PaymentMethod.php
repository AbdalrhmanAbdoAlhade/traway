<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'provider',
        'provider_token',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'is_default',
    ];

    protected $hidden = [
        'provider_token',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'exp_month' => 'integer',
            'exp_year' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // DB unique index on default_owner_key guarantees one default method per user (BR-017).
        static::saving(function (PaymentMethod $method) {
            $method->default_owner_key = $method->is_default ? $method->user_id : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
