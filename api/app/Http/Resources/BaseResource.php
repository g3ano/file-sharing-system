<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\MissingValue;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseResource extends JsonResource
{
    protected function getRelationships(array $relationships = [])
    {
        if (!is_array($relationships) || !$relationships) {
            return new MissingValue();
        }

        $relationships = $this->getValidRelationships($relationships);

        return $relationships ?: new MissingValue();
    }

    private function getValidRelationships(array $relationships = []): array
    {
        $validValues = [];

        foreach ($relationships as $relationship => $value) {
            if ($this->isInvalidValue($value)) {
                continue;
            }

            $validValues[$relationship] = $value;
        }

        return $validValues;
    }

    private function isInvalidValue(mixed $value): bool
    {
        return !$value ||
            $value instanceof MissingValue ||
            ($value instanceof JsonResource &&
                $value->resource instanceof MissingValue)
                ||
            ($value instanceof ResourceCollection &&
                $value->isEmpty());
    }

    protected function getAttributes(array $attributes = [])
    {
        if (!is_array($attributes) || !$attributes) {
            return new MissingValue();
        }

        return $attributes;
    }

    /**
     * Additional attributes to be included with response
     */
    protected function getMeta(): array|MissingValue
    {
        return new MissingValue();
    }
}
