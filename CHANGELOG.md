# Changelog

All notable changes to `waymaker` will be documented in this file.

## v0.0.1 - 2026-01-05

Initial release

## v0.3.1 - 2025-01-06

### Fixed

- Controllers in subdirectories (e.g., Auth/LoginController) are now properly discovered
- Fixed namespace generation for controllers in nested directories
- Added comprehensive test coverage for subdirectory controller discovery

## v0.3.0 - 2024-04-21

### Added

- Smart URI generation for RESTful controller methods
- Resource methods like show, edit, update, and destroy now automatically append {id} parameter
- Custom non-standard methods append the method name to the URI
- Test coverage for multiple methods in controllers
- Improved controller method discovery and handling

### Fixed

- Issue with duplicate URIs for controllers with multiple methods
- Route conflicts when methods use the same HTTP verb
