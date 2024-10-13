<?php

namespace App\Helpers;

use App\Models\Ability;

trait HasAbilityContext
{
    /**
     * Calculates context where ability is applied.
     */
    public function getUserAbilityContext(Ability &$ability)
    {
        $appliesTo = $ability->abilitable;
        $parentClassBasename = class_basename($ability->entity_type) ?? null;

        $ability->isAppliesToInstance = (bool) $appliesTo?->id;
        $ability->isAppliesToAll = !(bool) $appliesTo?->id;
        $ability->appliesTo = strtolower($parentClassBasename);
    }
}
