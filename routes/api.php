<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->group(function () {
        require __DIR__ . '/v1/user.php';
        require __DIR__ . '/v1/role.php';
    });
