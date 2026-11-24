<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'description' => $this->description,
            'permissions' => $this->permissions ?? [],
            'is_system'   => (bool) ($this->is_system ?? false),
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
