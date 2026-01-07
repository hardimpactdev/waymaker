# Contributing to Waymaker

Thank you for considering contributing to Waymaker! This document outlines the process for contributing to this project.

## Development Setup

1. Fork and clone the repository
2. Install dependencies:

```bash
composer install
```

3. Run the test suite to ensure everything is working:

```bash
composer test
```

## Making Changes

### Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) for code formatting. Before submitting a PR, run:

```bash
composer format
```

### Static Analysis

We use PHPStan for static analysis. Ensure your code passes:

```bash
composer analyse
```

### Testing

All new features and bug fixes should include tests. We use [Pest](https://pestphp.com/) for testing.

```bash
# Run all tests
composer test

# Run tests with coverage
composer test-coverage
```

#### Writing Tests

- Place tests in the `tests/` directory
- Use descriptive test names that explain what is being tested
- Test both happy paths and edge cases
- Use the `TestFixtures` trait for tests that need temporary controllers

Example test structure:

```php
<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
    $this->setupWaymaker();
});

afterEach(function () {
    $this->tearDownFixtures();
});

test('it generates routes for controller with Get attribute', function () {
    // Create a test controller
    $controllerContent = <<<'PHP'
    <?php
    namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

    use HardImpact\Waymaker\Get;

    class TestController
    {
        #[Get]
        public function index()
        {
            return 'Hello';
        }
    }
    PHP;

    file_put_contents($this->tempPath.'/TestController.php', $controllerContent);

    $definitions = Waymaker::generateRouteDefinitions();

    expect($definitions)->toContain("Route::get");
});
```

## Pull Request Process

1. Create a new branch for your feature or fix:
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. Make your changes and commit with clear, descriptive messages

3. Ensure all tests pass and code style is correct:
   ```bash
   composer format
   composer analyse
   composer test
   ```

4. Push your branch and create a Pull Request

5. Describe your changes in the PR description, including:
   - What the change does
   - Why it's needed
   - Any breaking changes
   - Related issues (if any)

## Reporting Issues

When reporting issues, please include:

- PHP version
- Laravel version
- Waymaker version
- Steps to reproduce
- Expected behavior
- Actual behavior
- Any relevant error messages or logs

## Questions?

If you have questions about contributing, feel free to open an issue for discussion.
