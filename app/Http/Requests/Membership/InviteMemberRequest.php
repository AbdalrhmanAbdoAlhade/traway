<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email'   => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'scopes'  => ['sometimes', 'array'],
            'scopes.*' => ['string'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }
}
