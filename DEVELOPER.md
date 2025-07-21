# Developer Documentation

This document provides a guide for developers who want to contribute to the CodeIgniter to Laravel Migration Tool.

## Project Structure

The project follows a standard Laravel structure, with the core logic located in the `app` directory.

```
app/
├── Console/
│   └── Commands/
│       └── MigrateCodeIgniterApp.php   # The main Artisan command that kicks off the migration.
├── Contracts/
│   ├── CIAnalyzerInterface.php         # Contract for analyzing a CodeIgniter project.
│   ├── CIConverterInterface.php        # Contract for converting CodeIgniter components to Laravel.
│   ├── CIMigrationCoordinatorInterface.php # Contract for coordinating the entire migration process.
│   └── NodeProcessorInterface.php      # Contract for processing individual PHP parser nodes.
├── Enums/
│   ├── CIVersion.php                   # Enum for CodeIgniter versions.
│   └── LaravelDatabaseDriver.php       # Enum for Laravel database drivers.
├── Factories/
│   ├── CIConfigProcessorFactory.php
│   ├── CIControllerProcessorFactory.php
│   ├── CIDatabaseProcessorFactory.php
│   ├── CIModelProcessorFactory.php
│   └── CIRouteProcessorFactory.php
├── Http/
│   └── ...                             # Standard Laravel HTTP kernel.
├── Models/
│   └── ...                             # Standard Laravel models.
├── Providers/
│   ├── AppServiceProvider.php
│   └── ConverterServiceProvider.php    # Binds the analyzer and converter implementations.
├── Services/
│   ├── FileHandlerService.php          # Handles file system operations.
│   ├── LaravelProjectService.php       # Manages the creation and modification of the new Laravel project.
│   ├── LaravelProjectSetupService.php  # Sets up a new Laravel project.
│   ├── LogService.php                  # Handles logging.
│   ├── PromptService.php               # Handles user prompts.
│   ├── StatusBarService.php            # Manages the progress bar display.
│   ├── Abstracts/
│   │   └── BaseCIMigrationService.php
│   ├── Analyzers/
│   │   ├── AbstractCIAnalyzerService.php # Base class for analyzers.
│   │   ├── CI2AnalyzerService.php
│   │   ├── CI3AnalyzerService.php
│   │   └── CI4AnalyzerService.php
│   ├── API/
│   │   ├── APICIProjectPreparationService.php
│   │   └── APIMigrationService.php
│   ├── CLI/
│   │   └── CLIMigrationService.php
│   ├── Converters/
│   │   ├── AbstractCIConverterService.php # Base class for converters.
│   │   ├── CI2ConverterService.php
│   │   ├── CI3ConverterService.php
│   │   └── CI4ConverterService.php
│   ├── Coordinators/
│   │   ├── AbstractCIMigrationCoordinatorService.php
│   │   ├── CI3MigrationCoordinatorService.php
│   │   └── CIMigrationCoordinatorService.php # The main service that orchestrates the migration.
│   ├── Parsers/
│   │   ├── AbstractParserVisitor.php
│   │   ├── GenericNodeVisitor.php
│   │   └── NodeProcessors/               # Contains the logic for processing different types of PHP code.
│   │       ├── CIConfigNodeProcessorBase.php
│   │       ├── CIControllerNodeProcessorBase.php
│   │       ├── CIDatabaseNodeProcessorBase.php
│   │       ├── CIModelNodeProcessorBase.php
│   │       ├── CIRouteNodeProcessorBase.php
│   │       ├── CI2/
│   │       ├── CI3/
│   │       └── CI4/
│   └── Utility/
│       └── PhpFileParser.php           # A wrapper around nikic/php-parser.
├── Support/
│   ├── DatabaseConnectionConfig.php
│   └── DirectoryManager.php
└── Traits/
    ├── HasConsoleOutput.php
    ├── HasDirectories.php
    └── HasStatusBar.php
```

## Core Concepts

The migration process is broken down into three main phases:

1.  **Analysis:** The `CIAnalyzerInterface` implementations scan the CodeIgniter project to identify its structure, version, and components (controllers, models, routes, etc.).
2.  **Conversion:** The `CIConverterInterface` implementations take the information from the analysis phase and convert the CodeIgniter components to their Laravel equivalents. This is where the bulk of the work happens.
3.  **Coordination:** The `CIMigrationCoordinatorInterface` implementations orchestrate the entire process, from creating the new Laravel project to running the analysis and conversion steps.

The conversion process itself is further broken down by using the `nikic/php-parser` library to parse the PHP code and then using a series of `NodeProcessorInterface` implementations to process the different types of nodes in the AST. This allows for a very granular and extensible approach to the conversion.

## How to Contribute

1.  **Fork the repository.**
2.  **Create a new branch for your feature or bug fix.**
3.  **Write your code.** Be sure to follow the existing code style and conventions.
4.  **Write tests for your code.** This is important!
5.  **Submit a pull request.**
