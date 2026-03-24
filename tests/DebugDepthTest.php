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

test('debug depth ordering output', function () {
    // Simple controller with prefix
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class DebugInspectionController
{
    protected static string $routePrefix = 'inspections';
    
    #[Get]
    public function index()
    {
        return 'List inspections';
    }
    
    #[Get(uri: 'create')]
    public function create()
    {
        return 'Create form';
    }
    
    #[Get(uri: '{inspection}')]
    public function show($inspection)
    {
        return 'Show inspection';
    }
}
PHP;

    file_put_contents($this->tempPath.'/DebugInspectionController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Filter to route lines only
    $routeLines = array_filter($definitions, fn ($line) => str_contains($line, 'Route::') && ! str_contains($line, 'Route::prefix'));

    expect($routeLines)->not->toBeEmpty();
});
