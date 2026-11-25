<?php

namespace App\Http\Requests\Hotel;

use Illuminate\Foundation\Http\FormRequest;

class SaveHotelRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is enforced by HotelPolicy in the controller.
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
            'description.*' => ['nullable', 'string', 'max:5000'],
            'amenities' => ['sometimes', 'nullable', 'array', 'max:60'],
            'amenities.*' => ['string', 'max:50'],
            'cancellation_policy' => ['sometimes', 'nullable', 'array:ar,en'],
            'cancellation_policy.*' => ['nullable', 'string', 'max:2000'],
            'country_code' => ['sometimes', 'nullable', 'string', 'size:2'],
            'city' => [$required, 'string', 'max:100'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'reception_phone' => ['sometimes', 'nullable', 'string', 'max:30', 'regex:/^\+?[0-9\s\-]{6,30}$/'],
            'contact_email' => ['sometimes', 'nullable', 'email', 'max:255'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'star_rating' => ['sometimes', 'nullable', 'integer', 'between:1,5'],
            'check_in_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'check_out_time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'cover_image' => ['sometimes', 'nullable', 'string', 'max:255'],
            'gallery' => ['sometimes', 'nullable', 'array', 'max:20'],
            'gallery.*' => ['string', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper($this->input('country_code'))]);
        }
    }
}
