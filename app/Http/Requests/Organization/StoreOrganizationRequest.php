<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'type'        => ['required', 'string', 'in:agency,hotel,airline,corporate,other'],
            'description' => ['nullable', 'string', 'max:2000'],
            'logo'        => ['nullable', 'image', 'max:2048'],
            'settings'    => ['nullable', 'array'],
        ];
    }
}
