<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_type',
        'module',
        'action',
        'description',
        'subject_type',
        'subject_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'method',
        'endpoint',
        'status_code',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'status_code' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function isAdminPanel(): bool
    {
        return $this->module === 'admin_panel';
    }

    public function isBooking(): bool
    {
        return $this->module === 'bookings';
    }

    public function isAdmin(): bool
    {
        return $this->actor_type === 'admin';
    }

    public function isCustomer(): bool
    {
        return $this->actor_type === 'customer';
    }

    public function getChanges(): array
    {
        return [
            'old' => $this->old_values ?? [],
            'new' => $this->new_values ?? [],
        ];
    }
}
