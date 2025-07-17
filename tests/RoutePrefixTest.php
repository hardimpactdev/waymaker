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
 * Test that route prefix is correctly applied to all routes.
 */
test('it applies route prefix to all routes including those with custom URIs', function () {
    // Create controller content with route prefix
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionEntryController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
    #[Get(uri: '{inspection_entry}/pdf')]
    public function pdf($inspection_entry) 
    {
        return 'pdf';
    }
    
    #[Get(uri: '{inspection_entry}/html')]
    public function html($inspection_entry) 
    {
        return 'html';
    }
    
    #[Get]
    public function index() 
    {
        return 'index';
    }
}
PHP;

    file_put_contents($this->tempPath.'/InspectionEntryController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the inspection routes
    $pdfRoute = null;
    $htmlRoute = null;
    $indexRoute = null;

    foreach ($definitions as $definition) {
        if (str_contains($definition, 'pdf')) {
            $pdfRoute = $definition;
        }
        if (str_contains($definition, 'html')) {
            $htmlRoute = $definition;
        }
        if (str_contains($definition, 'index') && str_contains($definition, 'InspectionEntryController')) {
            $indexRoute = $definition;
        }
    }

    // Assert routes exist
    expect($pdfRoute)->not->toBeNull();
    expect($htmlRoute)->not->toBeNull();
    expect($indexRoute)->not->toBeNull();

    // Assert that routes are within a prefix group
    expect($pdfRoute)->toContain("'{inspection_entry}/pdf'");
    expect($htmlRoute)->toContain("'{inspection_entry}/html'");
    expect($indexRoute)->toContain("''"); // Empty string for index route in group

    // Verify the prefix group exists
    $routesString = implode("\n", $definitions);
    expect($routesString)->toContain("Route::prefix('inspections')");
});

/**
 * Test that route prefix handles leading slashes correctly.
 */
test('it handles leading slashes in URIs with route prefix correctly', function () {
    // Create controller content with route prefix and leading slash in URI
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionEntryExportController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
    #[Get(uri: '/export')]
    public function export() 
    {
        return 'export';
    }
    
    #[Get(uri: 'reports')]
    public function reports() 
    {
        return 'reports';
    }
}
PHP;

    file_put_contents($this->tempPath.'/InspectionEntryExportController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the export routes
    $exportRoute = null;
    $reportsRoute = null;

    foreach ($definitions as $definition) {
        if (str_contains($definition, 'export') && str_contains($definition, 'InspectionEntryExportController')) {
            $exportRoute = $definition;
        }
        if (str_contains($definition, 'reports') && str_contains($definition, 'InspectionEntryExportController')) {
            $reportsRoute = $definition;
        }
    }

    // Assert routes exist
    expect($exportRoute)->not->toBeNull();
    expect($reportsRoute)->not->toBeNull();

    // In the new grouped format, routes within a prefix group don't include the prefix in the URI
    // The export route should be Route::get('export', ...) within the inspections group
    expect($exportRoute)->toContain("Route::get('export'");
    expect($reportsRoute)->toContain("Route::get('reports'");

    // Verify the routes are within a prefix group
    $hasInspectionsPrefix = false;
    foreach ($definitions as $definition) {
        if (str_contains($definition, "Route::prefix('inspections')")) {
            $hasInspectionsPrefix = true;
            break;
        }
    }
    expect($hasInspectionsPrefix)->toBeTrue();
});

/**
 * Test that constructor methods are not included in route generation.
 */
test('it excludes constructor methods from route generation', function () {
    // Create controller content with constructor
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class TestConstructorController
{
    protected static string $routePrefix = 'test';
    
    public function __construct()
    {
        // Constructor logic
    }
    
    #[Get]
    public function index() 
    {
        return 'index';
    }
}
PHP;

    file_put_contents($this->tempPath.'/TestConstructorController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Look for constructor route
    $constructorRoute = null;
    $indexRoute = null;

    foreach ($definitions as $definition) {
        if (str_contains($definition, '__construct')) {
            $constructorRoute = $definition;
        }
        if (str_contains($definition, 'index') && str_contains($definition, 'TestConstructorController')) {
            $indexRoute = $definition;
        }
    }

    // Assert constructor route does not exist
    expect($constructorRoute)->toBeNull();

    // But index route should exist
    expect($indexRoute)->not->toBeNull();
});
