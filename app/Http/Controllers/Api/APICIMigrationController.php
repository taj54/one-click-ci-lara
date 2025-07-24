<?php

namespace App\Http\Controllers\Api;

use App\Enums\CIVersion;
use App\Http\Controllers\Controller;
use App\Http\Requests\CIMigrateRequest;
use App\Services\API\APICIProjectPreparationService;
use App\Services\API\APIMigrationService;
use App\Services\FileHandlerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Http\JsonResponse;

class APICIMigrationController extends Controller
{
    /**
     * APICIMigrationController constructor.
     *
     * @param APIMigrationService $migrationService
     * @param APICIProjectPreparationService $preparationService
     * @param FileHandlerService $fileHandlerService
     */
    public function __construct(
        private APIMigrationService $migrationService,
        private APICIProjectPreparationService $preparationService,
        private FileHandlerService $fileHandlerService
    ) {}

    /**
     * Handles the upload of a CodeIgniter project ZIP file, extracts its contents,
     * and detects the CodeIgniter version.
     *
     * Validates the uploaded file to ensure it is a ZIP archive and within the size limit.
     * Extracts the ZIP file, sets the input directory for further processing, and attempts
     * to detect the CodeIgniter version using the migration service.
     *
     * @param  \Illuminate\Http\Request  $request  The incoming HTTP request containing the ZIP file.
     * @return \Illuminate\Http\JsonResponse       JSON response indicating success or failure,
     *                                             including detected version information if successful.
     */
    public function uploadAndDetectVersion(Request $request): JsonResponse
    {
        $request->validate([
            'ciProjectZip' => 'required|file|mimes:zip|max:51200',
        ]);

        try {
            $file = $request->file('ciProjectZip');
            $result = $this->preparationService->extractAndDetect($file->getPathname());

            $this->fileHandlerService->setInputDirectory($result['projectPath']);

            $version = $this->migrationService->detectCodeIgniterVersion();

            if($version === CIVersion::UNKNOWN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to detect CodeIgniter version.',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'File uploaded and version detected.',
                'uniqueId' => $result['uniqueId'],
                'version' => $version->value,
                'label' => $version->label(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Starts the migration process for a CodeIgniter project to Laravel.
     *
     * Validates the request, checks the project directory, sets up file handlers,
     * and invokes the migration service. Returns a JSON response with the migration report.
     *
     * @param  CIMigrateRequest  $request  The validated migration request.
     * @return \Illuminate\Http\JsonResponse       JSON response with migration status and report.
     */
    public function startMigration(CIMigrateRequest $request) //: JsonResponse
    {
        $validated = $request->validated();

        try {
            $ciProjectPath = $this->preparationService->getProjectPathFromUniqueId($validated['uniqueId']);

            if (!File::isDirectory($ciProjectPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found or expired.',
                ], 404);
            }
            $parentPath = dirname($ciProjectPath);
            $outputDIrectory = $parentPath . DIRECTORY_SEPARATOR . 'migrated';
            

            $this->setupFileHandler($ciProjectPath,  $outputDIrectory);
            $this->validateDirectories();
            $this->migrationService->setUserInputs(
                $validated['projectName'],
                $validated['laravelVersion'],
                filter_var($validated['installSail'], FILTER_VALIDATE_BOOLEAN)
            );

            $report = $this->migrationService->migrate();

            if ($report['conversion']['overall_success'] ?? false) {
                return response()->json([
                    'success' => true,
                    'message' => 'Migration completed.',
                    'report' => $report,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Migration failed.',
                'report' => $report,
                'error' => $report['conversion']['error'] ?? 'Unknown error',
            ], 500);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Set up file handler service with environment and paths.
     *
     * Configures the file handler service with the test environment directory,
     * input directory, and output directory for migration.
     *
     * @param string $input   Path to the input (CodeIgniter) project directory.
     * @param string $output  Path to the output (Laravel) project directory.
     * @return void
     */
    protected function setupFileHandler(string $input, string $output): void
    {
        $appPath = base_path();
        $testEnvDirectory = realpath($appPath . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'test-environment' . DIRECTORY_SEPARATOR);
        $this->fileHandlerService->setTestEnvDir($testEnvDirectory);
        $this->fileHandlerService->setInputDirectory($input);
        $this->fileHandlerService->setOutputDirectory($output);
        // Optionally pass CLI context to service (if needed)
        // $this->fileHandlerService->setConsole($this);
    }

    /**
     * Validates the input and output directories for migration.
     *
     * Checks if the input directory is not empty, paths are valid, and output directory can be created.
     *
     * @return bool True if all directory checks pass, false otherwise.
     */
    protected function validateDirectories(): bool
    {
        return $this->fileHandlerService->emptyCheckInputDirectory()
            && $this->fileHandlerService->inputDirectoryPathCheck()
            && $this->fileHandlerService->isInputDirectoryValid()
            && $this->fileHandlerService->isOutputDirectoryMakeValid();
    }
}
