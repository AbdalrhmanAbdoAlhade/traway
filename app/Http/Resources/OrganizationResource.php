<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'slug'          => $this->slug,
            'type'          => $this->type,
            'status'        => $this->status,
            'logo_url'      => $this->logo_url,
            'description'   => $this->description,
            'settings'      => $this->settings,
            'owner'         => new UserResource($this->whenLoaded('owner')),
            'members_count' => $this->whenCounted('members'),
            'my_role'       => $this->when(
                isset($this->pivot) || method_exists($this, 'currentUserRole'),
                fn () => $this->currentUserRole?->only(['id', 'name', 'slug'])
            ),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
