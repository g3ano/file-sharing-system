<?php

namespace App\Http\Resources\v1;

use App\Http\Resources\BaseResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

class AbilityResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "type" => "abilities",
            "attributes" => $this->getAttributes([
                "name" => $this->name,
                "title" => $this->title,
                "forbidden" => $this->forbidden,
                "createdAt" => $this->created_at,
            ]),
            "relationships" => $this->getRelationships([
                "abilitable" => $this->resolveMorphToManyResourceClass(
                    $this->whenLoaded("abilitable")
                ),
            ]),
            "meta" => $this->getMeta(),
        ];
    }

    protected function getMeta(): array|MissingValue
    {
        $result = [];
        $result["createdAt"] = $this->whenNotNull(
            $this->created_at->format("F j, Y")
        );

        $result["isAppliesToAll"] = $this->whenNotNull($this->isAppliesToAll);
        $result["isAppliesToInstance"] = $this->whenNotNull(
            $this->isAppliesToInstance
        );
        $result["appliesTo"] = $this->whenNotNull($this->appliesTo);

        return $result ?: new MissingValue();
    }

    protected function resolveMorphToManyResourceClass(
        Model|MissingValue|null $model = null
    ): JsonResource|MissingValue {
        if (!$model || $model instanceof MissingValue) {
            return new MissingValue();
        }

        $modelClass = get_class($model);
        $modelBaseName = class_basename($modelClass);
        $resourceClass = "App\\Http\\Resources\\v1\\{$modelBaseName}Resource";

        if (class_exists($resourceClass)) {
            return new $resourceClass($model);
        }

        return null;
    }
}
