<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/v1')->get('/', function () {
    return ['Laravel' => app()->version()];
});

require __DIR__ . '/auth.php';
