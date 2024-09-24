<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class RoleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'roles',
            'attributes' => $this->getAttributes([
                'name' => $this->name,
                'slug' => $this->slug,
                'context' => $this->context,
            ]),
            'relationships' => $this->getRelationships([
                'workspaces' => new WorkspaceCollection($this->whenLoaded('workspaces')),
                'projects' => new ProjectCollection($this->whenLoaded('projects')),
            ]),
        ];
    }
}
