<?php

namespace App\Http\Requests\OrganizationApplication;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'organization_name' => ['required', 'string', 'max:255'],
            'organization_type' => ['required', 'string', 'in:agency,hotel,airline,corporate,other'],
            'details'           => ['nullable', 'string', 'max:5000'],
        ];
    }
}
