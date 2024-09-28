<?php

namespace App\Http\Controllers;

use App\Helpers\Slugable;
use App\Helpers\Relatable;
use App\Helpers\HasResponse;
use App\Helpers\HasPaginatorMeta;
use App\Helpers\Orderable;

abstract class Controller
{
    use HasResponse;
    use Relatable;
    use Slugable;
    use HasPaginatorMeta;
    use Orderable;
}
