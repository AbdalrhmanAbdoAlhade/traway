<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BulkRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'from' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
            'weekdays' => ['sometimes', 'array', 'max:7'],
            'weekdays.*' => ['integer', 'between:0,6'],
            'price_cents' => ['sometimes', 'integer', 'between:100,100000000', 'prohibits:price_adjust_cents'],
            'price_adjust_cents' => ['sometimes', 'integer', 'between:-10000000,10000000'],
            'available_units' => ['sometimes', 'integer', 'between:0,1000'],
            'is_stop_sell' => ['sometimes', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (! $this->hasAny(['price_cents', 'price_adjust_cents', 'available_units', 'is_stop_sell'])) {
                    $validator->errors()->add('operation', 'Send at least one change: a price, a price adjustment, availability or stop-sell.');
                }
            },
        ];
    }
}
