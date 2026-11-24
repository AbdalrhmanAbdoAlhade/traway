<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // يُفترض أن Policy تتعامل مع التفصيل؛ هنا نمنع فقط الزوار
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'logo'        => ['sometimes', 'nullable', 'image', 'max:2048'],
            'settings'    => ['sometimes', 'array'],
        ];
    }
}
