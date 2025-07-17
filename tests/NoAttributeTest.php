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

/**
 * Test that methods without route attributes do not generate routes.
 */
test('it does not generate routes for methods without route attributes', function () {
    // Create controller with methods that should not generate routes
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class TestController
{
    private $service;
    
    public function __construct($service)
    {
        $this->service = $service;
    }
    
    public function publicMethodWithoutAttribute()
    {
        return 'This should not generate a route';
    }
    
    protected function protectedMethod()
    {
        return 'This should not generate a route';
    }
    
    private function privateMethod()
    {
        return 'This should not generate a route';
    }
    
    #[Get]
    public function index()
    {
        return 'This SHOULD generate a route';
    }
    
    public function anotherPublicMethod($param)
    {
        return 'This should not generate a route either';
    }
}
PHP;

    file_put_contents($this->tempPath.'/TestController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Convert to string for easier checking
    $routesString = implode("\n", $definitions);

    // Debug output
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Should only have one route - the index method with #[Get]
    $routeCount = 0;
    foreach ($definitions as $definition) {
        if (str_contains($definition, 'TestController') && str_contains($definition, 'Route::')) {
            $routeCount++;
        }
    }

    // Assert only one route is generated (for index method)
    expect($routeCount)->toBe(1);

    // Assert the index route exists
    expect($routesString)->toContain("Route::get('/test', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\TestController::class, 'index']");

    // Assert other methods do not generate routes
    expect($routesString)->not->toContain('__construct');
    expect($routesString)->not->toContain('publicMethodWithoutAttribute');
    expect($routesString)->not->toContain('protectedMethod');
    expect($routesString)->not->toContain('privateMethod');
    expect($routesString)->not->toContain('anotherPublicMethod');
});

/**
 * Test that constructor with dependency injection does not generate routes.
 */
test('it does not generate routes for constructors with dependency injection', function () {
    // Create controller similar to the user's example
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionController
{
    public function __construct(
        private InspectionService $inspectionService
    ) {}
    
    #[Get]
    public function index()
    {
        return 'Inspections list';
    }
    
    #[Get(uri: '{id}')]
    public function show($id)
    {
        return 'Show inspection';
    }
}

// Dummy service class
class InspectionService {}
PHP;

    file_put_contents($this->tempPath.'/InspectionController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Convert to string for easier checking
    $routesString = implode("\n", $definitions);

    // Debug output
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Count routes for InspectionController
    $routeCount = 0;
    foreach ($definitions as $definition) {
        if (str_contains($definition, 'InspectionController') && str_contains($definition, 'Route::')) {
            $routeCount++;
        }
    }

    // Should have exactly 2 routes (index and show)
    expect($routeCount)->toBe(2);

    // Assert constructor is not in routes
    expect($routesString)->not->toContain('__construct');

    // Assert the correct routes exist
    expect($routesString)->toContain("Route::get('/inspection', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\InspectionController::class, 'index']");
    expect($routesString)->toContain("Route::get('/inspection/{id}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\InspectionController::class, 'show']");
});
