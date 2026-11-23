<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaOfferingDocument extends Model
{
    use HasTranslations;

    protected $fillable = [
        'visa_offering_id',
        'document_type',
        'name',
        'is_mandatory',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'name' => 'array',
            'is_mandatory' => 'boolean',
        ];
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(VisaOffering::class, 'visa_offering_id');
    }
}
