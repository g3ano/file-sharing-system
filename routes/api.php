<?php

use Illuminate\Support\Facades\Route;

Route::prefix("v1")->group(function () {
    require __DIR__ . "/v1/user.php";
    require __DIR__ . "/v1/workspace.php";
    require __DIR__ . "/v1/project.php";
    require __DIR__ . "/v1/file.php";
    require __DIR__ . "/v1/storage.php";
});
