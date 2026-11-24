<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'email'           => $this->email,
            'organization_id' => $this->organization_id,
            'role'            => new RoleResource($this->whenLoaded('role')),
            'invited_by'      => new UserResource($this->whenLoaded('inviter')),
            'status'          => $this->status,
            'expires_at'      => $this->expires_at?->toISOString(),
            'accepted_at'     => $this->accepted_at?->toISOString(),
            'created_at'      => $this->created_at?->toISOString(),
        ];
    }
}
