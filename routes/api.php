<?php

use App\Http\Controllers\Api\APICIMigrationController;
use Illuminate\Support\Facades\Route;


Route::prefix('migration')->group(function () {
    // New endpoint for uploading and detecting CI version
    Route::post('upload-and-detect', [APICIMigrationController::class, 'uploadAndDetectVersion']);

    // Existing endpoint for starting the migration (now requires uniqueId)
    Route::post('start', [APICIMigrationController::class, 'startMigration']);
});
