<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

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
                'createAt' => $this->created_at,
            ]),
            'relationships' => $this->getRelationships([]),
        ];
    }
}
