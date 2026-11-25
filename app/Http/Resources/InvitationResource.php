<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Membership
 */
class InvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->user?->email,
            'organization_id' => $this->organization_id,
            'status' => $this->status,
            'job_title' => $this->job_title,
            'services' => $this->services(),
            'hotel_ids' => $this->hotelIds(),
            'invited_by' => new UserResource($this->whenLoaded('inviter')),
            'invited_at' => $this->invited_at?->toISOString(),
            'expires_at' => $this->invitation_expires_at?->toISOString(),
            'is_expired' => $this->invitation_expires_at?->isPast() ?? true,
        ];
    }
}
