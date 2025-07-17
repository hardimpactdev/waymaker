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
 * Test that duplicate route definitions are detected and handled.
 */
test('it prevents duplicate route definitions for the same URI and HTTP method', function () {
    // Create two controllers with the same route prefix and URI
    $controller1 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionEntryController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
    #[Get]
    public function index() 
    {
        return 'index from InspectionEntryController';
    }
}
PHP;

    $controller2 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionEntryListController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
    #[Get]
    public function index() 
    {
        return 'index from InspectionEntryListController';
    }
}
PHP;

    file_put_contents($this->tempPath.'/InspectionEntryController.php', $controller1);
    file_put_contents($this->tempPath.'/InspectionEntryListController.php', $controller2);

    // Generating routes should throw an exception due to duplicates
    expect(fn () => Waymaker::generateRouteDefinitions())
        ->toThrow(
            \RuntimeException::class,
            'Duplicate route detected: get /inspections'
        );
});

/**
 * Test that different HTTP methods on the same URI are allowed.
 */
test('it allows different HTTP methods on the same URI', function () {
    // Create a controller with multiple methods on the same URI
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Delete;

class ResourceController
{
    protected static string $routePrefix = 'resources';
    
    #[Get]
    public function index() 
    {
        return 'list resources';
    }
    
    #[Post]
    public function store() 
    {
        return 'create resource';
    }
    
    #[Get(uri: '{id}')]
    public function show($id) 
    {
        return 'show resource';
    }
    
    #[Delete(uri: '{id}')]
    public function destroy($id) 
    {
        return 'delete resource';
    }
}
PHP;

    file_put_contents($this->tempPath.'/ResourceController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Debug: print all routes
    // echo "\nGenerated routes:\n" . implode("\n", $definitions) . "\n\n";

    // Find the routes
    $getIndex = null;
    $postStore = null;
    $getShow = null;
    $deleteDestroy = null;

    foreach ($definitions as $definition) {
        // Routes are now grouped, so we look for the route without the prefix
        if (str_contains($definition, "Route::get('')") && str_contains($definition, 'ResourceController') && str_contains($definition, "'index']")) {
            $getIndex = $definition;
        }
        if (str_contains($definition, "Route::post('')") && str_contains($definition, 'ResourceController') && str_contains($definition, "'store']")) {
            $postStore = $definition;
        }
        if (str_contains($definition, "Route::get('{id}'") && str_contains($definition, 'ResourceController') && str_contains($definition, "'show']")) {
            $getShow = $definition;
        }
        if (str_contains($definition, "Route::delete('{id}'") && str_contains($definition, 'ResourceController') && str_contains($definition, "'destroy']")) {
            $deleteDestroy = $definition;
        }
    }

    // All routes should exist
    expect($getIndex)->not->toBeNull();
    expect($postStore)->not->toBeNull();
    expect($getShow)->not->toBeNull();
    expect($deleteDestroy)->not->toBeNull();
});
