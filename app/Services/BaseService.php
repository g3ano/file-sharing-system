<?php

namespace App\Services;

use App\Helpers\Slugable;
use App\Helpers\HasResponse;

abstract class BaseService
{
    use HasResponse;
    use Slugable;
}
