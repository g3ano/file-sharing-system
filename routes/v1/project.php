<?php

use App\Http\Controllers\v1\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("projects")
    ->controller(ProjectController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/new", "createProject");
        Route::get("/list", "getProjectList");

        Route::get("/deleted", "getDeletedProjectList");

        Route::delete("/delete/{projectID}", "deleteProject");
        Route::delete("/force-delete/{projectID}", "forceDeleteProject");
        Route::post("/restore/{projectID}", "restoreProject");

        Route::get("/members/list", "getProjectMemberList");
        Route::post("/members/add", "addProjectMembers");
        Route::post("/members/remove", "removeProjectMembers");
    });
