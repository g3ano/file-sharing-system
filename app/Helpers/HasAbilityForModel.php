<?php

namespace App\Helpers;

use App\Models\Ability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;

trait HasAbilityForModel
{
    /**
     * Returns builder to user abilities for a model instance or all instances.
     */
    public function prepareAbilitiesBuilderFor(
        Model|string|null $model = null,
        bool $broad = false
    ): Builder {
        $permissions = Models::table("permissions");
        $abilities = Models::table("abilities");
        $className = !is_null($model)
            ? (is_string($model)
                ? $model
                : get_class($model))
            : null;

        $query = Ability::query()->whereIn("id", function (
            QueryBuilder $query
        ) use ($permissions) {
            $query
                ->select("ability_id")
                ->from($permissions)
                ->where("entity_id", $this->getKey())
                ->where("entity_type", User::class);
        });

        if (!$model) {
            return $query;
        }

        if (!is_string($model)) {
            $query->where(function (Builder $query) use (
                $abilities,
                $model,
                $className,
                $broad
            ) {
                $query
                    ->where("{$abilities}.entity_type", $className)
                    ->where("{$abilities}.entity_id", $model->getKey());

                if ($broad) {
                    $query->orWhere("{$abilities}.entity_id", null);
                }
            });

            return $query;
        }

        $query->where("entity_type", $model);

        return $query;
    }
}
