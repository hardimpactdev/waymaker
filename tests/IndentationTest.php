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
 * Test that routes have correct indentation within groups.
 */
test('it generates routes with correct single tab indentation within groups', function () {
    // Create a simple controller
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class TestIndentController
{
    protected static string $routePrefix = 'test';
    protected static array $routeMiddleware = ['auth'];
    
    #[Get]
    public function index()
    {
        return 'index';
    }
    
    #[Post]
    public function store()
    {
        return 'store';
    }
}
PHP;

    file_put_contents($this->tempPath.'/TestIndentController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the routes within the group
    $foundGroup = false;
    $routesInGroup = [];

    foreach ($definitions as $line) {
        if (str_contains($line, "Route::prefix('test')")) {
            $foundGroup = true;
        } elseif ($foundGroup && str_contains($line, '});')) {
            break;
        } elseif ($foundGroup && str_contains($line, 'Route::')) {
            $routesInGroup[] = $line;
        }
    }

    expect($foundGroup)->toBeTrue();
    expect($routesInGroup)->not->toBeEmpty();

    // Check indentation - should be exactly 4 spaces (one tab equivalent)
    foreach ($routesInGroup as $route) {
        // Get the leading whitespace
        preg_match('/^(\s*)/', $route, $matches);
        $indent = $matches[1];

        // Should be exactly 4 spaces
        expect(strlen($indent))->toBe(4);
        expect($indent)->toBe('    ');

        // Ensure it's not double indented (8 spaces)
        expect($indent)->not->toBe('        ');
    }

    // Also check the raw output
    echo "\n=== Generated Routes ===\n";
    foreach ($definitions as $line) {
        // Show the line with visible spaces
        $visibleLine = str_replace(' ', '·', $line);
        echo $visibleLine."\n";
    }
    echo "=== End Routes ===\n";
});

/**
 * Test indentation for routes without groups.
 */
test('it generates routes without groups with no indentation', function () {
    // Create a controller without prefix or middleware
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class NoGroupController
{
    #[Get(uri: '/')]
    public function home()
    {
        return 'home';
    }
    
    #[Get(uri: 'about')]
    public function about()
    {
        return 'about';
    }
}
PHP;

    file_put_contents($this->tempPath.'/NoGroupController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find routes from NoGroupController
    $noGroupRoutes = array_filter($definitions, fn ($line) => str_contains($line, 'NoGroupController'));

    expect($noGroupRoutes)->not->toBeEmpty();

    // These routes should have no indentation
    foreach ($noGroupRoutes as $route) {
        // Get the leading whitespace
        preg_match('/^(\s*)/', $route, $matches);
        $indent = $matches[1];

        // Should have no indentation
        expect(strlen($indent))->toBe(0);
    }
});
