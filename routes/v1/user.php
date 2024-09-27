<?php

use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\RegisteredUserController;

Route::prefix('users')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/check', 'getIsUserAuth');

        Route::middleware(['auth:sanctum'])
            ->group(function () {
                Route::post('/register', [RegisteredUserController::class, 'store'])
                    ->name('register');
                Route::delete('/delete/{userID}', 'deleteUser');

                Route::get('/@me', 'getAuthUser');
                Route::get('/list', 'getUserList');
                Route::get('/list/deleted', 'getDeletedUserList');

                Route::get('/id/{userID}', 'getUserByID');
                Route::get('/slug/{userSlug}', 'getUserBySlug');

                Route::get('/search', 'searchUserList');
            });
    });
