<?php

use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('users')
    ->controller(UserController::class)
    ->group(function () {
        Route::middleware(['auth:sanctum'])
            ->group(function () {
                Route::get('/@me', 'getAuthUser');
            });

        Route::get('/check', 'getIsUserAuth');
        Route::get('/id/{id}', 'getUserById');
        Route::get('/slug/{slug}', 'getUserBySlug');
    });
