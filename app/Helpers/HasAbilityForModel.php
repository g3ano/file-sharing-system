<?php

namespace App\Helpers;

use App\Models\Ability;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Silber\Bouncer\Database\Models;
use Illuminate\Database\Eloquent\Model;

trait HasAbilityForModel
{
    /**
     * Returns builder to user abilities for a model instance or all instances.
     */
    public function getAbilitiesFor(Model|string $model, bool $broad = false): Builder
    {
        $permissions = Models::table('permissions');
        $abilities = Models::table('abilities');
        $className = is_string($model) ? $model : get_class($model);

        $query = Ability::query()
            ->join($permissions, "{$abilities}.id", "{$permissions}.ability_id")
            ->where("{$abilities}.entity_type", $className);

        if (!is_string($model)) {
            $query->where(function (Builder $query) use ($abilities, $model, $broad) {
                $query->where("{$abilities}.entity_id", $model->getKey());

                if ($broad) {
                    $query->orWhere("{$abilities}.entity_id", null);
                }
            });
        }

        $query->where("{$permissions}.entity_type", User::class)
            ->where("{$permissions}.entity_id", $this->getKey())
            ->select("{$abilities}.*")
            ->groupBy("{$abilities}.id");

        return $query;
    }
}
