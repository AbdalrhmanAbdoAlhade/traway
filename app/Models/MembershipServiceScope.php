<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipServiceScope extends Model
{
    public const SERVICES = ['flights', 'hotels', 'tours', 'visas', 'esim'];

    protected $fillable = [
        'membership_id',
        'service',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
