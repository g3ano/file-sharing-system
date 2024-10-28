<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\MissingValue;

class FileResource extends BaseResource
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
            "type" => "files",
            "attributes" => [
                "name" => $this->name,
                "extension" => $this->extension,
                "type" => $this->type,
                "size" => $this->size,
                "path" => $this->path,
                "createdAt" => $this->created_at,
            ],
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

        $result["deleted"] = is_null($this->deleted_at)
            ? new MissingValue()
            : [
                "isDeleted" => true,
                "deletedAt" => $this->deleted_at,
                "deletedAtFormatted" => $this->deleted_at->format("F j, Y"),
            ];

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
