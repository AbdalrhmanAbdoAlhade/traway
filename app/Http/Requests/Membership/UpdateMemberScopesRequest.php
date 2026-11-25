<?php

namespace App\Http\Requests\Membership;

use App\Models\MembershipServiceScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberScopesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission is enforced in MembershipService (employees.update).
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'services' => ['present', 'array'],
            'services.*' => ['string', Rule::in(MembershipServiceScope::services())],
            'hotel_ids' => ['present', 'array'],
            'hotel_ids.*' => ['integer'],
        ];
    }
}
