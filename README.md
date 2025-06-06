# CodeIgniter to Laravel Migration Tool

A command-line tool designed to automate the migration of CodeIgniter applications to Laravel.

## Table of Contents

*   [Introduction](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.1d46mf1slaxe)
    
*   [Features](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.7cry1nim4l24)
    
*   [Limitations](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.40skx05rdjx6)
    
*   [Requirements](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.x9elv3slr6mv)
    
*   [Installation](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.ii3ri5m4z8po)
    
*   [Usage](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.i5vpur4siwki)
    
*   [Configuration](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.z92o9wylp9x8)
    
*   [Troubleshooting](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.cjsteq2rrix8)
    
*   [Future Scope](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.w6thk3bn0ldh)
    
*   [Contributing](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.snfxgb8oia2d)
    
*   [License](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.2duopv23z3kn)
    
*   [Credits](https://docs.google.com/document/d/1diakm2k8WIRN3OWJoQ2lptdEeqWKTH0m1jjufsbgh_c/edit#bookmark=id.9039ue57fper)
    

## Introduction

The CodeIgniter to Laravel Migration Tool is a command-line application built to streamline the process of migrating existing CodeIgniter (CI) projects to the Laravel framework. While a complete, "one-click" migration is not feasible due to the architectural differences, this tool automates a significant portion of the work, providing clear guidance for the manual adjustments required.

## Features

This tool intelligently transforms your CI codebase, covering common patterns:

*   Basic Routing Conversion: Maps simple CI routes to Laravel's route definitions.
    
*   Database Schema Generation: Creates Laravel Migration files from your existing CI database schema.
    
*   Simple Model Mapping: Converts basic DB\_Active\_Record CRUD operations to Eloquent.
    
*   View Syntax Transformation: Converts basic <?php echo $var; ?> to {{ $var }} and simple control structures to Blade directives.
    
*   Configuration File Conversion: Translates core CI config files to Laravel's .env and config/\*.php structure.
    
*   Composer Setup: Generates an initial composer.json and sets up autoloading for your new Laravel project.
    
*   Migration Report: Provides a detailed report highlighting successful conversions, areas needing manual review, and unmigrated components.
    

## Limitations

It's important to understand that some aspects of the migration require manual review and intervention:

*   Complex Database Queries: Custom SQL, stored procedures, or highly optimized queries will need manual re-writing.
    
*   Custom CI Helpers, Libraries, Hooks: These have no direct Laravel equivalents and will require manual porting.
    
*   Authentication/Authorization: Requires significant manual rewriting to utilize Laravel's robust Auth system.
    
*   Third-Party CI Packages: Manual compatibility solutions or replacements will be needed.
    
*   Advanced View Logic: Complex inline PHP logic within views may require manual refinement.
    
*   Relationships: Database relationships (e.g., One-to-Many) are often difficult to infer automatically and will largely require manual definition in Eloquent models.
    

We aim to be transparent about these limitations, guiding you through the necessary manual steps.

## Requirements

To run the migration tool and the generated Laravel project, you'll need:

### For the Migration Tool:

*   PHP 7.4 or higher
    
*   Composer 2.0 or higher
    

### For the Source CodeIgniter Project:

*   CodeIgniter 3 (Primary Target)
    

### For the Target Laravel Project:

*   Laravel LTS (Long-Term Support) version (e.g., Laravel 10 LTS)
    

## Installation

Instructions for installing the tool will be provided here upon release. Typically, it will involve a Composer global command:

1.  Install the tool using Composer:  
    composer global require your-vendor/ci-to-laravel-migration  
      
    
2.  Ensure Composer's global bin directory is in your system's PATH.
    

## Usage

To initiate a migration:

1.  Prepare your CodeIgniter project: Ensure it's in a clean state (e.g., without unnecessary temporary files).
    
2.  Run the migration command:  
    ci-to-laravel-migrate migrate /path/to/your\_codeigniter\_project /path/to/your\_new\_laravel\_project  
      
    

*   Replace /path/to/your\_codeigniter\_project with the actual path to your CI project (can be a local directory or a path to a .zip archive).
    
*   Replace /path/to/your\_new\_laravel\_project with the desired path for the new Laravel project. This directory will be created by the tool.
    

3.  Review the Migration Report: Carefully examine the generated report for areas requiring manual attention.
    

## Configuration

This tool primarily manages internal configurations for the migration process. There is no user-facing config file for the tool itself. All input is provided via command-line arguments.

## Troubleshooting

### Common Issues

*   Composer installation fails: Ensure you have the correct version of Composer installed and your internet connection is stable.
    
*   PHP version mismatch: Verify your PHP version meets the tool's requirements.
    
*   Permissions issues: Ensure the tool has read/write permissions for the source and destination directories.
    

### Bug Reports

If you encounter an issue not listed here, please submit a bug report via the project's issue tracker. Include as much detail as possible, including:

*   Steps to reproduce the issue.
    
*   The exact command you ran.
    
*   Any error messages or stack traces.
    
*   Your PHP and Composer versions.
    

## Future Scope

We are continuously working to improve the tool. Future plans may include:

*   Expanding support for other CodeIgniter versions (e.g., CI4).
    
*   Enhanced automation for complex scenarios.
    
*   Integration with other Laravel development tools.
    
*   Further refinement of the migration report and guidance.
    

## Contributing

Contributions are welcome! If you'd like to contribute, please follow these guidelines:

*   Fork the repository and create a new branch for your feature or bug fix.
    
*   Write clear, concise code and ensure existing tests pass.
    
*   Add new tests to cover your changes.
    
*   Submit a pull request with a clear description of your modifications and their purpose.
    

## License

The CodeIgniter to Laravel Migration Tool is released under the MIT License.

## Credits

*   The CodeIgniter to Laravel Migration Tool was created by \[Taj\].
    
*   Special thanks to the open-source community and the creators of nikic/php-parser for their invaluable tools.