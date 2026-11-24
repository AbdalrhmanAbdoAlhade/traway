<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MembershipServiceScope extends Model
{
    /**
     * Valid values are defined once in config/authorization.php.
     *
     * @return array<int, string>
     */
    public static function services(): array
    {
        return config('authorization.service_scopes', ['flights', 'hotels', 'tours', 'visas', 'esim']);
    }

    protected $fillable = [
        'membership_id',
        'service',
    ];

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }
}
