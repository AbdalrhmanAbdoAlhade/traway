<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Membership
 */
class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'organization_id' => $this->organization_id,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'status' => $this->status,
            'is_owner' => (bool) $this->is_owner,
            'job_title' => $this->job_title,
            'roles' => $this->whenLoaded(
                'assignedRoles',
                fn () => RoleResource::collection($this->assignedRoles)
            ),
            'services' => $this->services(),
            'hotel_ids' => $this->hotelIds(),
            'accepted_at' => $this->accepted_at?->toISOString(),
            'suspended_at' => $this->suspended_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
