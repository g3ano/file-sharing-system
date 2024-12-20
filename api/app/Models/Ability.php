<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;
use Silber\Bouncer\Database\Ability as BouncerAbility;

class Ability extends BouncerAbility
{
    /**
     * Get the parent commentable model (post or video).
     */
    public function abilitable(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, "entity_type", "entity_id");
    }
}
