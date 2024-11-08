<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\ProjectController;
use App\Http\Controllers\v1\StatController;
use App\Http\Controllers\v1\StorageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("workspaces")
    ->controller(WorkspaceController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/create", "createWorkspace");

        Route::controller(StorageController::class)->group(function () {
            Route::get("/listed/storage/used", "getWorkspaceListUsedSpace");
            Route::get(
                "/listed/{workspaceID}/storage/used",
                "getWorkspaceUsedSpace"
            );

            Route::get(
                "/deleted/{workspaceID}/storage/used",
                "getDeletedWorkspaceUsedSpace"
            );
            Route::get(
                "/deleted/storage/used",
                "getDeletedWorkspaceListUsedSpace"
            );
        });

        Route::get("/listed", "getWorkspaceList");
        Route::get("/listed/count", "getWorkspaceListCount");
        Route::get("/listed/{workspaceID}", "getWorkspaceByID");

        Route::get("/deleted", "getDeletedWorkspaceList");
        Route::get("/deleted/count", "getDeletedWorkspaceListCount");
        Route::get("/deleted/{workspaceID}", "getDeletedWorkspaceByID");

        Route::get("/search", "searchWorkspaceList");

        Route::put("/{workspaceID}/update", "updateWorkspace");
        Route::delete("/{workspaceID}/delete", "deleteWorkspace");
        Route::delete("/{workspaceID}/force-delete", "forceDeleteWorkspace");
        Route::post("/{workspaceID}/restore", "restoreWorkspace");

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

        Route::controller(ProjectController::class)->group(function () {
            Route::get(
                "/{workspaceID}/projects/listed",
                "getWorkspaceProjectList"
            );
            Route::post("/{workspaceID}/projects/add", "addWorkspaceProject");
            Route::delete(
                "/{workspaceID}/projects/{projectID}/delete",
                "deleteWorkspaceProject"
            );
        });

        Route::controller(FileController::class)->group(function () {
            Route::get("/{workspaceID}/files/listed", "getWorkspaceFileList");
            Route::get(
                "/{workspaceID}/files/trashed",
                "getWorkspaceTrashedFileList"
            );
        });

        Route::controller(StatController::class)->group(function () {
            Route::get("/{workspaceID}/stats", "getWorkspaceStats");
        });
    });
