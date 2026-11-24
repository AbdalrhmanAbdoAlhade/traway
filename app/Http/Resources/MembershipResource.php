<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MembershipResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'organization_id' => $this->organization_id,
            'user'            => new UserResource($this->whenLoaded('user')),
            'role'            => new RoleResource($this->whenLoaded('role')),
            'status'          => $this->status,
            'scopes'          => $this->scopes ?? [],
            'grants'          => $this->grants ?? [],
            'joined_at'       => $this->joined_at?->toISOString(),
            'suspended_at'    => $this->suspended_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
