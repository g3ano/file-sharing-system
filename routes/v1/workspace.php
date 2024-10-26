<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("workspaces")
    ->controller(WorkspaceController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/new", "createWorkspace");
        Route::get("/list", "getWorkspaceList");
        Route::get("/list/{workspaceID}", "getWorkspaceByID");
        Route::get("/count/list", "getWorkspaceListCount");

        Route::get("/deleted", "getDeletedWorkspaceList");
        Route::get("/deleted/{workspaceID}", "getDeletedWorkspaceByID");

        Route::get("/user/{userID}", "getUserWorkspaceList");

        Route::get("/search", "searchWorkspaceList");

        Route::delete("/delete/{workspaceID}", "deleteWorkspace");
        Route::delete("/force-delete/{workspaceID}", "forceDeleteWorkspace");
        Route::post("/restore/{workspaceID}", "restoreWorkspace");

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
    });
