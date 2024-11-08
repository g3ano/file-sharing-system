<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\ProjectController;
use App\Http\Controllers\v1\StatController;
use App\Http\Controllers\v1\StorageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("projects")
    ->controller(ProjectController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/create", "createProject");
        Route::get("/listed", "getProjectList");
        Route::get("/listed/count", "getProjectListCount");
        Route::get("/listed/{projectID}/storage/used", [
            StorageController::class,
            "getProjectUsedSpace",
        ]);
        Route::get("/listed/{projectID}", "getProjectByID");

        Route::get("/deleted", "getDeletedProjectList");
        Route::get("/deleted/count", "getDeletedProjectListCount");
        Route::get("/deleted/{projectID}/storage/used", [
            StorageController::class,
            "getDeletedProjectUsedSpace",
        ]);
        Route::get("/deleted/{projectID}", "getDeletedProjectByID");

        Route::post("/{projectID}/restore", "restoreProject");
        Route::delete("/{projectID}/delete", "deleteProject");
        Route::delete("/{projectID}/force-delete", "forceDeleteProject");

        Route::get("/{projectID}/members", "getProjectMemberList");
        Route::post("/{projectID}/members/add", "addProjectMembers");
        Route::post("/{projectID}/members/remove", "removeProjectMembers");
        Route::get(
            "/{projectID}/members/{userID}/abilities",
            "getProjectMemberAbilities"
        );
        Route::post(
            "/{projectID}/members/{userID}/abilities",
            "updateProjectMemberAbilities"
        );

        Route::controller(FileController::class)->group(function () {
            Route::get("/{projectID}/files/listed", "getProjectFileList");
            Route::get(
                "/{projectID}/files/trashed",
                "getProjectTrashedFileList"
            );
            Route::post("/{projectID}/files/add", "addProjectFile");
            Route::delete(
                "/{projectID}/files/{fileID}/delete",
                "deleteProjectFile"
            );
        });

        Route::controller(StatController::class)->group(function () {
            Route::get("/{projectID}/stats", "getProjectStats");
        });
    });
