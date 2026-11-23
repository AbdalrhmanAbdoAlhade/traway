<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_name',
        'device_type',
        'device_token',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'device_token',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null;
    }

    public function revoke(): bool
    {
        return $this->update([
            'revoked_at' => now(),
        ]);
    }

    public function markAsUsed(): bool
    {
        return $this->update([
            'last_used_at' => now(),
        ]);
    }
}
