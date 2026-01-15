# AGENTS.md

Guidance for AI coding agents working in the Waymaker repository.

## Project Overview

Waymaker is a Laravel package providing attribute-based route generation. It scans controller
methods with PHP 8 attributes (`#[Get]`, `#[Post]`, etc.) and generates route definition files.

- **Namespace:** `HardImpact\Waymaker` | **PHP:** 8.4+ | **Laravel:** 10, 11, 12

## Commands

```bash
vendor/bin/pest                              # Run all tests
vendor/bin/pest tests/MiddlewareTest.php     # Run single test file
vendor/bin/pest --filter="merges middleware" # Run tests matching pattern
vendor/bin/phpstan analyse                   # PHPStan level 5
vendor/bin/pint                              # Laravel Pint (PSR-12)
composer test | composer analyse | composer format  # Aliases
```

## Code Style

**Formatting:** 4 spaces, LF endings, UTF-8, final newline, PSR-12 via Laravel Pint

**Import Order:** PHP internal (`ReflectionClass`) > Framework (`Illuminate\*`) > Third-party > Internal

**Naming:** Classes=PascalCase, methods/properties=camelCase, enums=UPPER_CASE

### Type Declarations (Always Required)
```php
private static ?string $controllerPath = null;
public function __construct(
    public ?string $uri = null,
    public array|string|null $middleware = null,
) {}
public static function generateRouteDefinitions(): array

/** @param array<string> $middleware */  // PHPDoc for array types
```

### PHP 8 Features (Use These)
```php
#[Get(uri: '{id}', middleware: 'auth')]   // Attributes + named args
enum HttpMethod: string { case GET = 'GET'; }
public function __construct(public ?string $uri = null) {}  // Property promotion
$name = match ($method?->name) { 'GET' => 'Get', default => null };
$wrapped = array_map(fn ($p) => '{'.$p.'}', $params);  // Arrow functions
$httpMethod?->name  // Nullsafe operator
```

### Error Handling
```php
throw new \RuntimeException("Duplicate route: {$uri}");  // Business logic errors
throw new \InvalidArgumentException("Not found: {$path}");  // Invalid input
try { require $file; } catch (\Throwable $e) { Log::error($e->getMessage()); }
if (! class_exists($class)) { continue; }  // Silent skip
```

## Testing (Pest PHP)

```php
<?php
use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
    $this->setupWaymaker();
});

afterEach(fn () => $this->tearDownFixtures());

test('generates routes', function () {
    $controller = <<<'PHP'
    <?php
    namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;
    use HardImpact\Waymaker\Get;
    class TestController {
        #[Get]
        public function index() {}
    }
    PHP;
    file_put_contents($this->tempPath.'/TestController.php', $controller);

    expect(Waymaker::generateRouteDefinitions())->toContain($expected);
});

// Expectations
expect($routes)->toContain($expected);
expect($routes)->not->toContain('__construct');
expect(fn () => $action())->toThrow(\RuntimeException::class, 'message');

// Architecture test (tests/ArchTest.php)
arch('no debugging')->expect(['dd', 'dump', 'ray'])->each->not->toBeUsed();

// Testing private methods
$reflection = new ReflectionClass(Waymaker::class);
$method = $reflection->getMethod('generateUri');
$method->setAccessible(true);
$result = $method->invokeArgs(null, $params);
```

## Key Patterns

### Controller Route Attributes
```php
class ArticleController extends Controller {
    protected static string $routePrefix = 'articles';
    protected static array $routeMiddleware = ['auth'];

    #[Get] public function index() {}
    #[Get(uri: '{id}')] public function show() {}
    #[Post(middleware: 'throttle:5,1')] public function store() {}
}
```

### v2.0+ Breaking Change
All methods MUST have explicit route attributes (`#[Get]`, `#[Post]`, etc.).
No automatic HTTP method inference. Methods without attributes are ignored.

### URI Generation Rules
- Controller name becomes base URI (kebab-cased, "Controller" suffix removed)
- Route prefixes always applied, even to custom URIs
- RESTful methods (`show`, `edit`, `update`, `destroy`) auto-add `{id}`
- `/` as custom URI becomes root path

### Route Sorting (by specificity)
1. Fewer segments first
2. Static before parameterized
3. Alphabetically

### Duplicate Detection
Same HTTP method + URI throws `RuntimeException` with both locations.

## File Structure

```
src/
  Waymaker.php           # Main route generation logic
  RouteAttribute.php     # Base attribute class
  Get.php, Post.php...   # HTTP method attributes
  Enums/HttpMethod.php   # HTTP method enum
  Commands/              # Artisan commands
tests/
  Traits/TestFixtures.php  # Test helper trait
  Factories/               # Dynamic controller generation
  Fixtures/Controllers/    # Static test fixtures
```

## Do Not Modify
- `routes/waymaker.php` - Generated output
- `vendor/`, `build/` - Dependencies and artifacts
