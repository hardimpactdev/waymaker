<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();

    // Create a controller with multiple methods
    $multiMethodControllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use Illuminate\Routing\Controller;
use Inertia\Response;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Enums\HttpMethod;

class MultiMethodController extends Controller
{
    // First method
    public function index(): Response
    {
        return inertia('Test/Index');
    }

    // Second method
    public function show(): Response
    {
        return inertia('Test/Show');
    }

    // Third method
    #[Post]
    public function store(): Response
    {
        return inertia('Test/Store');
    }
}
PHP;

    // Create a temporary directory if it doesn't exist
    if (! is_dir($this->tempPath)) {
        mkdir($this->tempPath, 0777, true);
    }

    // Write the controller to a file
    file_put_contents($this->tempPath.'/MultiMethodController.php', $multiMethodControllerContent);

    // Set up Waymaker to use our temp path
    Waymaker::setControllerPath($this->tempPath, 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp');
});

afterEach(function () {
    $this->tearDownFixtures();
});

/**
 * Test that multiple methods in a controller are properly handled
 */
test('it generates routes for controllers with multiple methods', function () {
    // Generate routes
    $routes = Waymaker::generateRouteDefinitions();

    // Build the expected route definitions
    $namespace = 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp';

    $expectedRoutes = [
        // Group header
        '// /',
        // Route definitions
        "Route::get('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'index'])->name('Controllers.MultiMethodController.index');",
        "Route::get('/multi-method/{id}', [\\{$namespace}\\MultiMethodController::class, 'show'])->name('Controllers.MultiMethodController.show');",
        "Route::post('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'store'])->name('Controllers.MultiMethodController.store');",
    ];

    // Check each expected route definition
    foreach ($expectedRoutes as $route) {
        expect($routes)->toContain($route);
    }

    // Make sure we have the correct number of elements in the routes array
    expect(count($routes))->toBe(4);

    // Verify specific routes have the correct URIs
    expect($routes[1])->toBe("Route::get('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'index'])->name('Controllers.MultiMethodController.index');");
    expect($routes[2])->toBe("Route::get('/multi-method/{id}', [\\{$namespace}\\MultiMethodController::class, 'show'])->name('Controllers.MultiMethodController.show');");

    // Verify the order of routes
    expect($routes[0])->toBe('// /');
});
