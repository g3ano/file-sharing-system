<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserWorkspace extends Pivot
{
    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = true;
}
