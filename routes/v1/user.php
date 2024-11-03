<?php

use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\RegisteredUserController;
use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\ProjectController;
use App\Http\Controllers\v1\WorkspaceController;

Route::prefix("users")
    ->controller(UserController::class)
    ->group(function () {
        Route::get("/check", "getIsUserAuth");

        Route::middleware(["auth:sanctum"])->group(function () {
            Route::post("/register", [
                RegisteredUserController::class,
                "store",
            ])->name("register");

            Route::get("/me", "getAuthUser");

            Route::get("/list", "getUserList");
            Route::get("/list/count", "getUserListCount");
            Route::get("/list/search", "searchUserList");
            Route::get("/list/{userID}", "getUserByID");

            Route::get("/deleted", "getDeletedUserList");
            Route::get("/deleted/count", "getDeletedUserListCount");
            Route::get("/deleted/search", "searchDeletedUserList");
            Route::get("/deleted/{userID}", "getDeletedUserByID");

            Route::post("/{userID}/restore", "restoreUser");
            Route::put("/{userID}/update", "updateUser");
            Route::delete("/{userID}/delete", "deleteUser");
            Route::delete("/{userID}/force-delete", "forceDeleteUser");

            Route::get("/{userID}/abilities", "getUserAbilities");
            Route::get("/{userID}/abilities/global", "getUserGlobalAbilities");
            Route::post(
                "/{userID}/abilities/global",
                "updateUserGlobalAbilities"
            );
            Route::get(
                "/{userID}/abilities/{targetID}",
                "getUserAbilitiesForUser"
            );
            Route::post(
                "/{userID}/abilities/{targetID}",
                "updateUserAbilitiesForUser"
            );

            Route::get("/{userID}/workspaces", [
                WorkspaceController::class,
                "getUserWorkspaceList",
            ]);

            Route::get("/{userID}/projects", [
                ProjectController::class,
                "getUserProjectList",
            ]);
            Route::get("/{userID}/files/listed", [
                FileController::class,
                "getUserFileList",
            ]);
            Route::get("/{userID}/files/trashed", [
                FileController::class,
                "getUserTrashedFileList",
            ]);
        });
    });
