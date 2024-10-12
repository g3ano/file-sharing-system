<?php

use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\v1\Auth\RegisteredUserController;

Route::prefix("users")
    ->controller(UserController::class)
    ->group(function () {
        Route::get("/check", "getIsUserAuth");

        Route::middleware(["auth:sanctum"])->group(function () {
            Route::post("/register", [
                RegisteredUserController::class,
                "store",
            ])->name("register");
            Route::delete("/delete/{userID}", "deleteUser");
            Route::post("/restore/{userID}", "restoreUser");
            Route::delete("/force-delete/{userID}", "forceDeleteUser");

            Route::get("/me", "getAuthUser");
            Route::get("/list", "getUserList");
            Route::get("/list/deleted", "getDeletedUserList");
            Route::get("/count/list", "getUserListCount");
            Route::get("/count/list/deleted", "getDeletedUserListCount");

            Route::post("/{userID}/workspaces/add", "addUserWorkspaces");
            Route::post("/{userID}/workspaces/remove", "removeUserWorkspaces");

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
            Route::get("/{userID}", "getUserByID");
        });
    });
