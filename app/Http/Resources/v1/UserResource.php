<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;

class UserResource extends BaseResource
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
            "type" => "users",
            "attributes" => $this->getAttributes([
                "firstName" => $this->first_name,
                "lastName" => $this->last_name,
                "slug" => $this->slug,
                "username" => $this->username,
                "email" => $this->includeEmail ?? null ? $this->email : null,
                "createdAt" => $this->created_at,
            ]),
            "relationships" => $this->getRelationships([
                "roles" => new RoleCollection($this->whenLoaded("roles")),
            ]),
            "meta" => $this->getMeta(),
        ];
    }

    protected function getMeta(): array|MissingValue
    {
        $result = [];
        $result["createdAtFormatted"] = $this->created_at->format("F j, Y");

        if ($this->capabilities) {
            $result["capabilities"] = $this->capabilities;
        }

        $result["isOwner"] = $this->whenNotNull($this->isOwner);

        $result["deleted"] = is_null($this->deleted_at)
            ? new MissingValue()
            : [
                "isDeleted" => true,
                "deletedAt" => $this->deleted_at,
                "deletedAtFormatted" => $this->deleted_at->format("F j, Y"),
            ];

        return $result ?: new MissingValue();
    }
}
