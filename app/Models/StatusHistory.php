<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use LogicException;

class StatusHistory extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'subject_type',
        'subject_id',
        'from_status',
        'to_status',
        'actor_user_id',
        'actor_membership_id',
        'note',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Status history is append-only.');
        });

        static::deleting(function () {
            throw new LogicException('Status history is append-only.');
        });
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function actorMembership(): BelongsTo
    {
        return $this->belongsTo(Membership::class, 'actor_membership_id');
    }
}
