# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

-   Test all: `vendor/bin/pest` or `composer test`
-   Test single: `vendor/bin/pest tests/path/to/TestFile.php`
-   Static analysis: `vendor/bin/phpstan analyse` or `composer analyse`
-   Code formatting: `vendor/bin/pint` or `composer format`
-   Test with coverage: `vendor/bin/pest --coverage` or `composer test-coverage`

## Code Style Guidelines

-   PHP 8.4 with strict typing
-   PSR-12 formatting (enforced by Laravel Pint)
-   PSR-4 autoloading (`HardImpact\Waymaker\` namespace)
-   4 spaces for indentation, LF line endings
-   PHPStan Level 5 static analysis
-   Use type declarations for properties, parameters, and return types
-   Follow Laravel package conventions (Spatie's laravel-package-tools)
-   PascalCase for classes, camelCase for methods/properties
-   Use PHP 8 attributes for metadata (#[Route])
-   Use enums for constants (see HttpMethod enum)
-   Prefer explicit null handling with nullable types (?string)

## Known Issues and Patterns

1. **Route Attribute Requirements (v2.0+)**

    - All controller methods must have explicit route attributes (#[Get], #[Post], etc.) to generate routes
    - Methods without route attributes are ignored
    - No automatic HTTP method inference based on method names

2. **URI Generation Logic**

    - Controller name becomes the base URI (kebab-cased)
    - Route prefixes are always applied, even to custom URIs
    - Custom URIs are appended to the base/prefix
    - Special handling for '/' as custom URI (becomes root path)

3. **Multiple Methods in Controllers**

    - Controllers can have multiple methods with different HTTP verbs
    - Duplicate routes (same HTTP method + URI) throw RuntimeException
    - Routes are automatically ordered by specificity (static before parameterized)

4. **Configuration Options**
    - Controller-level prefix and middleware via static properties
    - Method-level customization via Route attributes
    - No configuration file needed (removed in v2.0)

## Code Improvements Made

-   Fixed route conflicts when controllers have multiple methods with the same HTTP verb
-   Implemented RESTful URI generation based on method name convention
-   Improved controller method discovery and filtering
-   Enhanced test coverage for multiple methods
-   Updated documentation to reflect new functionality

## File Structure

-   `src/Waymaker.php` - Main class implementing route generation logic
-   `src/Route.php` - Attribute class for route customization
-   `src/Enums/HttpMethod.php` - Enum for HTTP methods
-   `tests/` - Comprehensive test suite
