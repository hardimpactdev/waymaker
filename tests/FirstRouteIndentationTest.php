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
 * Test that the first route in a group has correct indentation.
 */
test('first route in group has same indentation as other routes', function () {
    // Create multiple controllers to ensure we have multiple groups
    $controller1 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class AFirstController
{
    protected static string $routePrefix = 'alpha';
    protected static array $routeMiddleware = ['auth'];
    
    #[Get]
    public function index()
    {
        return 'index';
    }
}
PHP;

    $controller2 = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class BSecondController
{
    protected static string $routePrefix = 'beta';
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
    
    #[Get(uri: 'show')]
    public function show()
    {
        return 'show';
    }
}
PHP;

    file_put_contents($this->tempPath.'/AFirstController.php', $controller1);
    file_put_contents($this->tempPath.'/BSecondController.php', $controller2);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Display all lines with line numbers and visible whitespace
    echo "\n=== All Generated Routes ===\n";
    foreach ($definitions as $index => $line) {
        $lineNum = str_pad($index + 1, 3, ' ', STR_PAD_LEFT);
        $visibleLine = str_replace(' ', '·', $line);
        echo "{$lineNum}: {$visibleLine}\n";
    }
    echo "=== End Routes ===\n";

    // Find all route lines within groups
    $inGroup = false;
    $firstRouteInGroup = true;
    $routeIndentations = [];

    foreach ($definitions as $line) {
        if (str_contains($line, '->group(function () {')) {
            $inGroup = true;
            $firstRouteInGroup = true;
        } elseif ($line === '});') {
            $inGroup = false;
        } elseif ($inGroup && str_contains($line, 'Route::')) {
            // Capture indentation
            preg_match('/^(\s*)/', $line, $matches);
            $indent = $matches[1];

            $routeIndentations[] = [
                'line' => $line,
                'indent' => $indent,
                'indent_length' => strlen($indent),
                'is_first' => $firstRouteInGroup,
            ];

            $firstRouteInGroup = false;
        }
    }

    // Check all routes have the same indentation
    expect(count($routeIndentations))->toBeGreaterThan(0);

    $expectedIndent = '    '; // 4 spaces
    foreach ($routeIndentations as $route) {
        expect($route['indent_length'])->toBe(4);
        expect($route['indent'])->toBe($expectedIndent);

        // Specifically check that first routes don't have different indentation
        if ($route['is_first']) {
            echo "\nFirst route in group: ".str_replace(' ', '·', $route['line'])."\n";
            expect($route['indent'])->toBe($expectedIndent);
        }
    }
});

/**
 * Test edge case: single route in a group.
 */
test('single route in group has correct indentation', function () {
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class SingleRouteController
{
    protected static string $routePrefix = 'single';
    protected static array $routeMiddleware = ['auth'];
    
    #[Get]
    public function index()
    {
        return 'index';
    }
}
PHP;

    file_put_contents($this->tempPath.'/SingleRouteController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the single route
    $routeLine = null;
    foreach ($definitions as $line) {
        if (str_contains($line, 'SingleRouteController')) {
            $routeLine = $line;
            break;
        }
    }

    expect($routeLine)->not->toBeNull();

    // Check indentation
    preg_match('/^(\s*)/', $routeLine, $matches);
    $indent = $matches[1];

    echo "\nSingle route: ".str_replace(' ', '·', $routeLine)."\n";

    expect(strlen($indent))->toBe(4);
    expect($indent)->toBe('    ');
});
