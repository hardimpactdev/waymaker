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
 * Test that specific routes are placed before parameterized routes.
 */
test('it orders specific routes before parameterized routes', function () {
    // Create multiple controllers with conflicting routes
    $inspectionController = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionController
{
    protected static string $routePrefix = 'inspections';
    
    #[Get]
    public function index() 
    {
        return 'index';
    }
    
    #[Get(uri: '{id}')]
    public function show($id) 
    {
        return 'show';
    }
}
PHP;

    $exportController = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionExportController
{
    protected static string $routePrefix = 'inspections';
    
    #[Get(uri: 'export')]
    public function export() 
    {
        return 'export';
    }
}
PHP;

    $createController = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class InspectionCreateController
{
    protected static string $routePrefix = 'inspections';
    
    #[Get(uri: 'create')]
    public function create() 
    {
        return 'create';
    }
}
PHP;

    file_put_contents($this->tempPath.'/InspectionController.php', $inspectionController);
    file_put_contents($this->tempPath.'/InspectionExportController.php', $exportController);
    file_put_contents($this->tempPath.'/InspectionCreateController.php', $createController);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the positions of specific routes
    $indexPos = null;
    $showPos = null;
    $exportPos = null;
    $createPos = null;

    foreach ($definitions as $index => $definition) {
        // Routes are now grouped, so we look for the route without the prefix
        if (str_contains($definition, "Route::get('')") && str_contains($definition, 'InspectionController') && str_contains($definition, 'index')) {
            $indexPos = $index;
        }
        if (str_contains($definition, "Route::get('{id}'") && str_contains($definition, 'InspectionController')) {
            $showPos = $index;
        }
        if (str_contains($definition, "Route::get('export'") && str_contains($definition, 'InspectionExportController')) {
            $exportPos = $index;
        }
        if (str_contains($definition, "Route::get('create'") && str_contains($definition, 'InspectionCreateController')) {
            $createPos = $index;
        }
    }

    // Assert routes exist
    expect($indexPos)->not->toBeNull();
    expect($showPos)->not->toBeNull();
    expect($exportPos)->not->toBeNull();
    expect($createPos)->not->toBeNull();

    // Assert that specific routes come before parameterized routes
    expect($exportPos)->toBeLessThan($showPos, 'Export route should come before {id} route');
    expect($createPos)->toBeLessThan($showPos, 'Create route should come before {id} route');
});

/**
 * Test that routes are sorted by specificity within the same prefix group.
 */
test('it sorts routes by specificity within the same prefix group', function () {
    // Create a controller with various route types
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class ResourceController
{
    protected static string $routePrefix = 'resources';
    
    #[Get]
    public function index() 
    {
        return 'index';
    }
    
    #[Get(uri: 'search')]
    public function search() 
    {
        return 'search';
    }
    
    #[Get(uri: 'export/pdf')]
    public function exportPdf() 
    {
        return 'exportPdf';
    }
    
    #[Get(uri: 'export/csv')]
    public function exportCsv() 
    {
        return 'exportCsv';
    }
    
    #[Get(uri: '{id}')]
    public function show($id) 
    {
        return 'show';
    }
    
    #[Get(uri: '{id}/edit')]
    public function edit($id) 
    {
        return 'edit';
    }
    
    #[Post(uri: '{id}/comments')]
    public function storeComment($id) 
    {
        return 'storeComment';
    }
    
    #[Get(uri: '{resource}/{id}')]
    public function showNested($resource, $id) 
    {
        return 'showNested';
    }
}
PHP;

    file_put_contents($this->tempPath.'/ResourceController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find positions of routes
    $positions = [];
    foreach ($definitions as $index => $definition) {
        if (str_contains($definition, "'/resources'") && str_contains($definition, 'index')) {
            $positions['index'] = $index;
        }
        if (str_contains($definition, "'/resources/search'")) {
            $positions['search'] = $index;
        }
        if (str_contains($definition, "'/resources/export/pdf'")) {
            $positions['exportPdf'] = $index;
        }
        if (str_contains($definition, "'/resources/export/csv'")) {
            $positions['exportCsv'] = $index;
        }
        if (preg_match("/'\\/resources\\/{id}'/", $definition) && str_contains($definition, 'show')) {
            $positions['show'] = $index;
        }
        if (str_contains($definition, "'/resources/{id}/edit'")) {
            $positions['edit'] = $index;
        }
        if (str_contains($definition, "'/resources/{id}/comments'")) {
            $positions['comments'] = $index;
        }
        if (str_contains($definition, "'/resources/{resource}/{id}'")) {
            $positions['nested'] = $index;
        }
    }

    // Assert all routes were found
    expect(count($positions))->toBe(8);

    // Assert ordering based on depth-first, then static before parameterized

    // Within depth 2 routes: static should come before parameterized
    expect($positions['search'])->toBeLessThan($positions['show'], 'Static route /search should come before /{id}');

    // Depth 2 routes should come before depth 3 routes
    expect($positions['show'])->toBeLessThan($positions['exportPdf'], '/{id} (depth 2) should come before /export/pdf (depth 3)');
    expect($positions['show'])->toBeLessThan($positions['exportCsv'], '/{id} (depth 2) should come before /export/csv (depth 3)');

    // Within depth 3: static routes before parameterized
    expect($positions['exportPdf'])->toBeLessThan($positions['edit'], 'Static /export/pdf should come before parameterized /{id}/edit');
    expect($positions['exportCsv'])->toBeLessThan($positions['edit'], 'Static /export/csv should come before parameterized /{id}/edit');

    // Depth 3 routes should come before depth 4 routes
    expect($positions['edit'])->toBeLessThan($positions['nested'], '/{id}/edit (depth 3) should come before /{resource}/{id} (depth 4)');
});
