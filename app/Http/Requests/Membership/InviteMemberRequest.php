<?php

namespace App\Http\Requests\Membership;

use App\Models\MembershipServiceScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced in MembershipService (employees.create).
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'job_title' => ['nullable', 'string', 'max:120'],
            'services' => ['sometimes', 'array'],
            'services.*' => ['string', Rule::in(MembershipServiceScope::services())],
            'hotel_ids' => ['sometimes', 'array'],
            'hotel_ids.*' => ['integer'],
        ];
    }
}
