<?php

namespace App\Helpers;

use App\Models\Ability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\select;

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

        /**
         * @var Builder
         */
        $query = Ability::query()
            ->join($permissions, "{$abilities}.id", "{$permissions}.ability_id")
            ->where("{$permissions}.entity_id", $this->getKey())
            ->where("{$permissions}.entity_type", User::class)
            ->select(["{$abilities}.*", "{$permissions}.forbidden"]);

        if (!$model) {
            return $broad
                ? $query->whereNull("{$abilities}.entity_id")
                : $query;
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

        $query->where("{$abilities}.entity_type", $model);

        //either return non-nullable entity_id records, or the opposite!
        return $broad
            ? $query->where("{$abilities}.entity_id", null)
            : $query->where("{$abilities}.entity_id", "!=", null);
    }
}
