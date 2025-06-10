<?php

use App\Http\Controllers\Api\APICIMigrationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('migration')->group(function () {
    // New endpoint for uploading and detecting CI version
    Route::post('upload-and-detect', [APICIMigrationController::class, 'uploadAndDetectVersion']);

    // Existing endpoint for starting the migration (now requires uniqueId)
    Route::post('start', [APICIMigrationController::class, 'startMigration']);
});
