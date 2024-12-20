<?php

use App\Http\Controllers\v1\FileController;
use Illuminate\Support\Facades\Route;

Route::prefix("files")
    ->controller(FileController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::post("/create", "createFile");
        Route::get("/list", "getFileList");
        Route::get("/list/count", "getFileListCount");
        Route::get("/list/search", "searchFileList");
        Route::get("/trashed", "getDeletedFileList");
        Route::get("/trashed/count", "getDeletedFileListCount");
        Route::get("/trashed/search", "searchDeleteFileList");

        Route::get("/{fileID}/download", "downloadFile");
        Route::post("/{fileID}/rename", "renameFile");

        Route::delete("/{fileID}/delete", "deleteFile");
        Route::post("/{fileID}/restore", "restoreFile");
        Route::delete("/{fileID}/force-delete", "forceDeleteFile");

        Route::get("/{fileID}/abilities/{userID}", "getUserAbilitiesForFile");
        Route::post(
            "/{fileID}/abilities/{userID}",
            "updateUserAbilitiesForFile"
        );
    });
