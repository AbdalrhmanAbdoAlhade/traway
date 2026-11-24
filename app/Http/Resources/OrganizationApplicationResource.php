<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'organization_name' => $this->organization_name,
            'organization_type' => $this->organization_type ?? null,
            'details'           => $this->details ?? null,
            'applicant'         => new UserResource($this->whenLoaded('applicant')),
            'status'            => $this->status,
            'reviewed_by'       => new UserResource($this->whenLoaded('reviewer')),
            'reviewed_at'       => $this->reviewed_at?->toISOString(),
            'rejection_reason'  => $this->rejection_reason,
            'created_at'        => $this->created_at?->toISOString(),
        ];
    }
}
