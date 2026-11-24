<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayoutAccount extends Model
{
    use SoftDeletes;

    public const TYPE_BANK = 'bank';

    public const TYPE_WALLET = 'wallet';

    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'organization_id',
        'type',
        'label',
        'account_name',
        'account_number',
        'iban',
        'bank_name',
        'swift_code',
        'country_code',
        'currency',
        'is_default',
        'status',
        'verified_by',
        'verified_at',
        'created_by',
    ];

    protected $hidden = [
        'account_number',
        'iban',
    ];

    protected function casts(): array
    {
        return [
            'account_number' => 'encrypted',
            'iban' => 'encrypted',
            'is_default' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Mirrors payment_methods: one default account per organization, enforced by a unique column.
        static::saving(function (PayoutAccount $account) {
            $account->default_owner_key = $account->is_default ? $account->organization_id : null;
        });

        // Soft-deleting a default account must free the one-default-per-organization
        // slot, otherwise no new default account can ever be created. The column is
        // persisted before the soft delete, which only writes deleted_at.
        static::deleting(function (PayoutAccount $account) {
            if ($account->default_owner_key !== null) {
                $account->forceFill(['is_default' => false])->saveQuietly();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class);
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }
}
