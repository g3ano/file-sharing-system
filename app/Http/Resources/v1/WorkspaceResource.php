<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Resources\MissingValue;

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
            "id" => $this->id,
            "type" => "workspaces",
            "attributes" => $this->getAttributes([
                "name" => $this->name,
                "slug" => $this->slug,
                "description" => $this->description,
                "createdAt" => $this->created_at,
            ]),
            "relationships" => $this->getRelationships([
                "owner" => new UserResource($this->whenLoaded("owner")),
                "members" => new UserCollection($this->whenLoaded("members")),
            ]),
            "meta" => $this->getMeta(),
        ];
    }

    protected function getMeta(): array|MissingValue
    {
        $result = [];
        $result["createdAt"] = $this?->created_at?->format("F j, Y");

        if ($this->capabilities) {
            $result["capabilities"] = $this->capabilities;
        }

        $result["isOwner"] = $this->whenNotNull($this->isOwner);

        return $result ?: new MissingValue();
    }
}
