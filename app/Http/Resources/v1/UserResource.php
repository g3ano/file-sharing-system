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
            'id' => $this->id,
            'type' => 'users',
            'attributes' => $this->getAttributes([
                'firstName' => $this->first_name,
                'lastName' => $this->last_name,
                'slug' => $this->slug,
                'username' => $this->username,
                'email' => $this->includeEmail ?? null
                    ? $this->email
                    : null,
                'createdAt' => $this->created_at,
            ]),
            'relationships' => $this->getRelationships([
                'roles' => new RoleCollection($this->whenLoaded('roles')),
            ]),
            'meta' => $this->getMeta(),
        ];
    }

    protected function getMeta(): array|MissingValue
    {
        $result = [];

        if ($this->abilities) {
            $result['abilities'] = $this->abilities;
        }

        return $result ?: new MissingValue();
    }
}
