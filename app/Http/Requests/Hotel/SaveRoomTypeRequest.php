<?php

namespace App\Http\Requests\Hotel;

use App\Models\RoomType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveRoomTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'array:ar,en'],
            'name.en' => [$required, 'string', 'max:150'],
            'name.ar' => ['nullable', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'array:ar,en'],
            'description.*' => ['nullable', 'string', 'max:3000'],
            'max_adults' => [$required, 'integer', 'between:1,10'],
            'max_children' => ['sometimes', 'integer', 'between:0,10'],
            'max_occupancy' => [$required, 'integer', 'between:1,20'],
            'total_units' => [$required, 'integer', 'between:1,1000'],
            // Cents. The pay-now discount is a platform commercial term and is not editable here (BR-067).
            'base_price_cents' => [$required, 'integer', 'between:100,100000000'],
            'amenities' => ['sometimes', 'nullable', 'array', 'max:60'],
            'amenities.*' => ['string', 'max:50'],
            'cancellation_policy' => ['sometimes', Rule::in([
                RoomType::CANCELLATION_FREE_UNTIL_DEADLINE,
                RoomType::CANCELLATION_NON_REFUNDABLE,
            ])],
            'free_cancellation_hours_before' => ['sometimes', 'nullable', 'integer', 'between:1,720'],
            'allows_pay_at_hotel' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
