<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix('workspaces')
    ->controller(WorkspaceController::class)
    ->group(function () {
        Route::post('/new', 'createWorkspace');
        Route::get('/list', 'getWorkspaceList');

        Route::get('/user/id/{userID}', 'getUserJoinedWorkspaceListByID');
        Route::get('/user/slug/{userSlug}', 'getUserJoinedWorkspaceListBySlug');
        Route::post('/user/add/{userID}', 'addUserToWorkspaces');

        Route::post('/{workspaceID}/members/new', 'addWorkspaceMembers');
        Route::post('/{workspaceID}/members/remove', 'removeWorkspaceMembers');
        Route::get('/{workspaceID}/members/list', 'getWorkspaceMembers');
    });
