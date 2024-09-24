<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class BaseCollection extends ResourceCollection
{
    public function paginationInformation($request, $paginated, $default)
    {
        return [
            'pagination' => [
                'page' => $default['meta']['current_page'],
                'pages' => $default['meta']['last_page'],
            ],
        ];
    }
}
