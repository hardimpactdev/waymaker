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
 * Test that routes without groups have no indentation.
 */
test('routes without prefix or middleware have no indentation', function () {
    // Create a controller without prefix or middleware
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class NoGroupDashboardController
{
    #[Get(uri: '/', middleware: ['auth', 'active'])]
    public function show()
    {
        return 'dashboard';
    }
    
    #[Post(uri: '/settings')]
    public function updateSettings()
    {
        return 'settings updated';
    }
}
PHP;

    file_put_contents($this->tempPath.'/NoGroupDashboardController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the dashboard routes
    $dashboardRoutes = array_filter($definitions, fn ($line) => str_contains($line, 'NoGroupDashboardController'));

    expect(count($dashboardRoutes))->toBe(2);

    // Check that these routes have NO indentation
    foreach ($dashboardRoutes as $route) {
        // Get the leading whitespace
        preg_match('/^(\s*)/', $route, $matches);
        $indent = $matches[1];

        // Should have no indentation
        expect(strlen($indent))->toBe(0);
        expect($route)->toStartWith('Route::');
    }
});

/**
 * Test mixed scenario with grouped and non-grouped routes.
 */
test('mixed grouped and non-grouped routes have correct indentation', function () {
    // Controller with group
    $controller1 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class GroupedController
{
    protected static string $routePrefix = 'admin';
    protected static array $routeMiddleware = ['auth'];
    
    #[Get]
    public function index()
    {
        return 'index';
    }
}
PHP;

    // Controller without group
    $controller2 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class NoGroupPublicController
{
    #[Get(uri: '/')]
    public function home()
    {
        return 'home';
    }
}
PHP;

    file_put_contents($this->tempPath.'/GroupedController.php', $controller1);
    file_put_contents($this->tempPath.'/NoGroupPublicController.php', $controller2);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Check grouped route has indentation
    $groupedRoute = null;
    foreach ($definitions as $line) {
        if (str_contains($line, 'GroupedController')) {
            $groupedRoute = $line;
            break;
        }
    }

    expect($groupedRoute)->not->toBeNull();
    preg_match('/^(\s*)/', $groupedRoute, $matches);
    expect(strlen($matches[1]))->toBe(4); // Should be indented

    // Check non-grouped route has NO indentation
    $publicRoute = null;
    foreach ($definitions as $line) {
        if (str_contains($line, 'NoGroupPublicController')) {
            $publicRoute = $line;
            break;
        }
    }

    expect($publicRoute)->not->toBeNull();
    preg_match('/^(\s*)/', $publicRoute, $matches);
    expect(strlen($matches[1]))->toBe(0); // Should NOT be indented
});
