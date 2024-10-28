<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\ProjectController;
use App\Http\Controllers\v1\StorageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("projects")
    ->controller(ProjectController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/create", "createProject");
        Route::get("/list", "getProjectList");
        Route::get("/list/count", "getProjectListCount");
        Route::get("/list/{projectID}/storage/used", [
            StorageController::class,
            "getProjectUsedSpace",
        ]);
        Route::get("/list/{projectID}", "getProjectByID");

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
            "getProjectMemberAbilities"
        );

        Route::get("/{projectID}/files", [
            FileController::class,
            "getProjectFileList",
        ]);
    });
