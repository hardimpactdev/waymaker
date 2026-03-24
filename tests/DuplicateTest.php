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

test('it handles multiple methods with the same HTTP verb and URI', function () {
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use Illuminate\Routing\Controller;
use HardImpact\Waymaker\Get;

class TestDuplicateController extends Controller
{
    #[Get]
    public function index()
    {
        return "Index";
    }

    #[Get(uri: "{id}")]
    public function show()
    {
        return "Show";
    }
}
PHP;

    file_put_contents($this->tempPath.'/TestDuplicateController.php', $controllerContent);

    $routes = Waymaker::generateRouteDefinitions();

    // Convert routes to string for easier inspection
    $routesString = implode("\n", $routes);

    // Check if methods generate routes with different URIs
    expect($routesString)->toContain("Route::get('/test-duplicate', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\TestDuplicateController::class, 'index'])")
        ->and($routesString)->toContain("Route::get('/test-duplicate/{id}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\TestDuplicateController::class, 'show'])")
        ->and($routesString)->toContain('TestDuplicateController.index')
        ->and($routesString)->toContain('TestDuplicateController.show');
});
