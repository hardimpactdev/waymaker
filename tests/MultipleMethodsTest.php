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
use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Enums\HttpMethod;

class MultiMethodController extends Controller
{
    // First method
    #[Get]
    public function index(): Response
    {
        return inertia('Test/Index');
    }

    // Second method
    #[Get(uri: '{id}')]
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
        // Route definitions
        "Route::get('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'index'])->name('MultiMethodController.index');",
        "Route::post('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'store'])->name('MultiMethodController.store');",
        "Route::get('/multi-method/{id}', [\\{$namespace}\\MultiMethodController::class, 'show'])->name('MultiMethodController.show');",
    ];

    // Check each expected route definition
    foreach ($expectedRoutes as $route) {
        expect($routes)->toContain($route);
    }

    // Make sure we have the correct number of elements in the routes array
    expect(count($routes))->toBe(3);

    // Verify specific routes have the correct URIs (static routes come before parameterized routes)
    expect($routes[0])->toBe("Route::get('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'index'])->name('MultiMethodController.index');");
    expect($routes[1])->toBe("Route::post('/multi-method', [\\{$namespace}\\MultiMethodController::class, 'store'])->name('MultiMethodController.store');");
    expect($routes[2])->toBe("Route::get('/multi-method/{id}', [\\{$namespace}\\MultiMethodController::class, 'show'])->name('MultiMethodController.show');");
});
