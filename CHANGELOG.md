# Changelog

All notable changes to `waymaker` will be documented in this file.

## v0.2.0 - 2026-01-30

### New Features

- **Invokable Controller Support**: Single-action controllers using `__invoke` method are now fully supported
  - Root route invokables: `HomeController::__invoke()` → `Route::get('/', ...)`
  - Prefixed invokables: `ContactController` with `$routePrefix = 'contact'` → grouped routes
  - Custom URI invokables: `#[Get(uri: 'about-us')]` → custom paths
  - Parameter support: `#[Get(parameters: ['id'])]` → dynamic routes
  - Route naming: Uses controller name only (e.g., `HomeController` not `HomeController.__invoke`)
  - Full middleware support at both controller and route levels
  - Works alongside regular multi-method controllers

### Improvements

- Enhanced route generation to detect and properly handle `__invoke` methods
- Improved URI generation for invokable controllers (no controller name in path)
- Route names exclude `__invoke` suffix for cleaner naming

**Full Changelog**: https://github.com/hardimpactdev/waymaker/compare/v0.1.1...v0.2.0

## v0.1.2 - 2026-01-15

### Documentation

- Added AGENTS.md with comprehensive AI coding agent guidelines
- Converted CLAUDE.md to symlink for OpenCode compatibility
- Enhanced code style documentation with examples

**Full Changelog**: https://github.com/hardimpactdev/waymaker/compare/v0.1.1...v0.1.2

## v0.1.1 - 2026-01-08

### New Features

- Added Laravel Boost integration with AI guidelines for route attributes
- Added release skill for standardized GitHub releases

### Documentation

- Document available route attributes and parameters
- Include example controller with route attributes
- Add route naming conventions reference

**Full Changelog**: https://github.com/hardimpactdev/waymaker/compare/v0.1.0...v0.1.1

## v0.1.0 - 2026-01-07

### First Stable Release

Waymaker generates route files based on your public controller methods using PHP attributes.

#### Features

- Attribute-based route generation with `#[Get]`, `#[Post]`, `#[Put]`, `#[Patch]`, `#[Delete]` attributes
- Smart route grouping with automatic prefix and middleware consolidation
- Laravel route file generation at `routes/waymaker.php`
- Automatic controller scanning with subdirectory support
- Integration with [Laravel Wayfinder](https://github.com/laravel/wayfinder) for TypeScript route definitions
- Route parameter support with model binding (e.g., `parameters: ['article:slug']`)
- Controller-level route prefixes and middleware via static properties

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
