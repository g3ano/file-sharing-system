<?php

namespace App\Helpers;

use Illuminate\Http\Request;

trait HasPaginatorMeta
{
    public $limit = 10;
    public $page = 1;
    public $maxLimit = 50;

    public function getPaginatorMetadata(Request $request): array
    {
        return [
            $request->query('page') ?? $this->page,
            min($request->query('limit') ?? $this->limit, $this->maxLimit),
        ];
    }
}
