<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix('workspaces')
    ->controller(WorkspaceController::class)
    ->group(function () {
        Route::post('/new', 'createWorkspace');
        Route::get('/list', 'getWorkspaceList');

        Route::post('/{workspaceID}/members/new', 'addWorkspaceMembers');
        Route::post('/{workspaceID}/members/remove', 'removeWorkspaceMembers');
        Route::get('/{workspaceID}/members/list', 'getWorkspaceMembers');

        Route::get('/user/id/{userID}', 'getUserJoinedWorkspaceListByID');
        Route::get('/user/slug/{userSlug}', 'getUserJoinedWorkspaceListBySlug');
    });
