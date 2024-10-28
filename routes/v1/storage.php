<?php

use App\Http\Controllers\v1\FileController;
use App\Http\Controllers\v1\StorageController;
use Illuminate\Support\Facades\Route;

Route::prefix("storage")
    ->controller(StorageController::class)
    ->middleware(["auth:sanctum"])
    ->group(function () {
        Route::get("/disk", "getDiskSpaceData");
    });
