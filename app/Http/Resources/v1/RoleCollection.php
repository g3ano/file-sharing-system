<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseCollection;
use Illuminate\Http\Request;

class RoleCollection extends BaseCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return parent::toArray($request);
    }
}
