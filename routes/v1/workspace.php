<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("workspaces")
    ->controller(WorkspaceController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/new", "createWorkspace");
        Route::get("/list", "getWorkspaceList");
        Route::get("/count/list", "getWorkspaceListCount");

        Route::get("/user/{userID}", "getUserWorkspaceList");

        Route::get("/search", "searchWorkspaceList");

        Route::get("/{workspaceID}/members", "getWorkspaceMembers");
        Route::post("/{workspaceID}/members/add", "addWorkspaceMembers");
        Route::post("/{workspaceID}/members/remove", "removeWorkspaceMembers");
        Route::get(
            "/{workspaceID}/members/{userID}/abilities",
            "getWorkspaceMemberAbilities"
        );
        Route::post(
            "/{workspaceID}/members/{userID}/abilities",
            "updateWorkspaceMemberAbilities"
        );

        Route::get("/{workspaceID}", "getWorkspaceByID");
    });
