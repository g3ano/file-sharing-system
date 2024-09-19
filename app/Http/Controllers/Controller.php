<?php

namespace App\Http\Controllers;

use App\Helpers\HasResponse;
use App\Helpers\Relatable;
use App\Helpers\Slugable;

abstract class Controller
{
    use HasResponse;
    use Relatable;
    use Slugable;
}
