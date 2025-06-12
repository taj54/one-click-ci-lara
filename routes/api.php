<?php

use App\Http\Controllers\Api\APICIMigrationController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\File;

Route::prefix('migration')->group(function () {
    // New endpoint for uploading and detecting CI version
    Route::post('upload-and-detect', [APICIMigrationController::class, 'uploadAndDetectVersion']);

    // Existing endpoint for starting the migration (now requires uniqueId)
    Route::post('start', [APICIMigrationController::class, 'startMigration']);
});

// Route::get('/debug-composer', function () {
//     $output = null;
//     $resultCode = null;
//     exec('composer --version 2>&1', $output, $resultCode);

//     return response()->json([
//         'output' => $output,
//         'result_code' => $resultCode,
//         'which_composer' => exec('where composer'),
//         'env_path' => getenv('PATH'),
//         'user' => exec('whoami'),
//     ]);
// });


// Route::get('/debug-composer', function () {
//     $output = '';
//     $errorOutput = '';
//     $resultCode = 1;

//     try {

//         $env = [
//             'PATH' => getenv('PATH') . ';' . env('PHP_PATH'),
//             'APPDATA' => getenv('APPDATA') ?: 'C:\\Users\\Default\\AppData\\Roaming',
//             // Add any other env vars you want here
//         ];
//         $composerExecutable = 'C:\composer\composer.phar';
//         $process = new Process(
//             [
//                 'php',
//                 $composerExecutable,
//                 'create-project',
//                 'laravel/laravel=8.x',
//                 'test-project',
//             ],
//             'C:\Migration helper apps\test-environment\test_CI3',   // working directory (null means current)
//             $env,   // environment variables array
//             null,   // input (null = none)
//             10 * 60    // timeout in seconds (optional, adjust as needed)
//         );
//         $process->run();

//         $output = $process->getOutput();
//         $errorOutput = $process->getErrorOutput();
//         $resultCode = $process->getExitCode();
//     } catch (\Throwable $e) {
//         $errorOutput = 'Exception: ' . $e->getMessage();
//     }

//     // Try to locate composer path using `where` (Windows) or `which` (Linux/macOS)
//     $composerPath = '';
//     try {
//         $lookupCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ['where', 'composer'] : ['which', 'composer'];
//         $locateProcess = new Process($lookupCmd, null, ['PATH' => getenv('PATH') . ';' . env('PHP_PATH')]);
//         $locateProcess->run();
//         $composerPath = trim($locateProcess->getOutput());
//     } catch (\Throwable $e) {
//         $composerPath = 'Error: ' . $e->getMessage();
//     }

//     // Detect the web server user
//     $user = '';
//     try {
//         $userCmd = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? ['whoami'] : ['id', '-un'];
//         $userProcess = new Process($userCmd);
//         $userProcess->run();
//         $user = trim($userProcess->getOutput());
//     } catch (\Throwable $e) {
//         $user = 'Error: ' . $e->getMessage();
//     }

//     return response()->json([
//         'output' => $output,
//         'error_output' => $errorOutput,
//         'result_code' => $resultCode,
//         'composer_path' => $composerPath,
//         'env_path' => getenv('PATH'),
//         'user' => $user,
//     ]);
// });

Route::get('/exec-create-laravel-composer', function () {
    $output = [];
    $resultCode = 1;

    $workingDir = 'C:\\Migration helper apps\\test-environment\\test_CI3';
    $composerPhar = 'C:\\composer\\composer.phar';
    $targetDir = 'test-project-' . date('Ymd_His');
    $fullTargetPath = $workingDir . '\\' . $targetDir;

    // Remove the directory if it exists (be careful with this!)
    if (File::isDirectory($fullTargetPath)) {
        File::deleteDirectory($fullTargetPath);
    }

    $cmd = 'cd /d "' . $workingDir . '" && php "' . $composerPhar . '" create-project laravel/laravel=8.x ' . $targetDir;

    try {
        exec($cmd, $output, $resultCode);
    } catch (\Throwable $e) {
        $output[] = 'Exception: ' . $e->getMessage();
        $resultCode = 1;
    }

    return response()->json([
        'command' => $cmd,
        'output' => implode("\n", $output),
        'result_code' => $resultCode,
        'user' => exec('whoami'),
    ]);
});



// Route::get('/fix-composer-config', function () {
//     $output = [];
//     $resultCode = 0;

//     // Option 1: disable secure HTTP enforcement (less secure)
//     // exec('php C:\\composer\\composer.phar config -g secure-http false 2>&1', $output, $resultCode);

//     // Or Option 2: force packagist repo to HTTPS (recommended)
//     exec('php C:\\composer\\composer.phar config -g repos.packagist composer https://repo.packagist.org 2>&1', $output, $resultCode);

//     return response()->json([
//         'output' => implode("\n", $output),
//         'result_code' => $resultCode,
//     ]);
// });

