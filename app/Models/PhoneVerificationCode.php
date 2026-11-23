<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneVerificationCode extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'code_hash',
        'channel',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $hidden = [
        'code_hash',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function hasAttemptsRemaining(int $maximum = 5): bool
    {
        return $this->attempts < $maximum;
    }

    public function isSms(): bool
    {
        return $this->channel === 'sms';
    }

    public function isWhatsApp(): bool
    {
        return $this->channel === 'whatsapp';
    }
}
