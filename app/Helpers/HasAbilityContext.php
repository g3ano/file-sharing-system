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
        $parentClassBasename = class_basename($ability->entity_type);

        $ability->isAppliesToInstance = (bool) $appliesTo;
        $ability->isAppliesToAll = !(bool) $appliesTo;
        $ability->appliesTo = strtolower($parentClassBasename);
    }
}
