<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class EsimPackage extends Model
{
    protected $fillable = [
        'provider',
        'provider_package_id',
        'country_code',
        'name',
        'data_mb',
        'validity_days',
        'cost_cents',
        'markup_bp',
        'sale_price_cents',
        'is_active',
        'is_stale',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'cost_cents' => 'integer',
            'sale_price_cents' => 'integer',
            'is_active' => 'boolean',
            'is_stale' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
