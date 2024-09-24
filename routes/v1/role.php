<?php

use App\Http\Controllers\v1\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')
    ->controller(RoleController::class)
    ->group(function () {
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/grant/global/{userID}', 'grantUserGlobalRole');
            Route::post('/grant/workspace/{userID}', 'grantUserWorkspaceRole');
            Route::post('/grant/project/{userID}', 'grantUserProjectRole');

            Route::get('/user/@me', 'getAuthUserRoles');
            Route::get('/user/check/@me', 'getAuthUserIsRole');
            Route::get('/user/{userID}', 'getUserRoles');
            Route::get('/user/check/{userID}', 'getUserIsRole');
        });
    });
