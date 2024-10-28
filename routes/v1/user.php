<?php

use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\RegisteredUserController;
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

            Route::post("/{userID}/restore", "restoreUser");
            Route::put("/{userID}/update", "updateUser");
            Route::delete("/{userID}/delete", "deleteUser");
            Route::delete("/{userID}/force-delete", "forceDeleteUser");

            Route::get("/me", "getAuthUser");

            Route::get("/list", "getUserList");
            Route::get("/list/count", "getUserListCount");

            Route::get("/deleted", "getDeletedUserList");
            Route::get("/deleted/count", "getDeletedUserListCount");

            Route::get("/search", "searchUserList");
            Route::get("/search/deleted", "searchDeletedUserList");

            Route::get("/{userID}/abilities", "getUserAbilities");
            Route::get("/{userID}/abilities/global", "getUserGlobalAbilities");
            Route::get(
                "/{userID}/abilities/{targetID}",
                "getUserAbilitiesForUser"
            );
            Route::post(
                "/{userID}/abilities/global",
                "updateUserGlobalAbilities"
            );
            Route::post(
                "/{userID}/abilities/{targetUserID}",
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
            Route::get("/{userID}", "getUserByID");
        });
    });
