<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class WorkspaceResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => 'workspaces',
            'attributes' => $this->getAttributes([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
                'createdAt' => $this->created_at,
            ]),
        ];
    }
}
