<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'  => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => [
                'sometimes', 'nullable', 'string', 'max:20',
                Rule::unique('users', 'phone')->ignore($this->user()->id),
            ],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'locale' => ['sometimes', 'string', 'max:10'],
        ];
    }
}
