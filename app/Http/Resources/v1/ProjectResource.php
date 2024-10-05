<?php

namespace App\Http\Resources\v1;

use Illuminate\Http\Request;
use App\Http\Resources\BaseResource;
use Illuminate\Http\Resources\MissingValue;

class ProjectResource extends BaseResource
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
            'type' => 'projects',
            'attributes' => $this->attributes([
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
                'createdAt' => $this->created_at,
            ]),
            'meta' => $this->getMeta(),
        ];
    }

    protected function getMeta(): array|MissingValue
    {
        $result = [];
        $result['createdAt'] = $this->created_at->format('F j, Y');

        return $result ?: new MissingValue();
    }
}
