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
 * Test that routes are ordered by depth within groups.
 */
test('routes within groups are ordered by depth', function () {
    // Create controller with routes of varying depth
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class GroupedDepthController
{
    protected static string $routePrefix = 'api';
    
    // Depth 1
    #[Get]
    public function index()
    {
        return 'index';
    }
    
    // Depth 2 - parameterized
    #[Get(uri: '{id}')]
    public function show($id)
    {
        return 'show';
    }
    
    // Depth 2 - static
    #[Get(uri: 'create')]
    public function create()
    {
        return 'create';
    }
    
    // Depth 3 - static
    #[Get(uri: 'admin/users')]
    public function adminUsers()
    {
        return 'admin users';
    }
    
    // Depth 3 - parameterized
    #[Get(uri: '{id}/edit')]
    public function edit($id)
    {
        return 'edit';
    }
    
    // Depth 4
    #[Get(uri: 'admin/users/export/csv')]
    public function exportCsv()
    {
        return 'export csv';
    }
}
PHP;

    file_put_contents($this->tempPath.'/GroupedDepthController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Extract route URIs within the group
    $inGroup = false;
    $routeUris = [];

    foreach ($definitions as $line) {
        if (str_contains($line, "Route::prefix('api')")) {
            $inGroup = true;
        } elseif ($line === '});') {
            $inGroup = false;
        } elseif ($inGroup && str_contains($line, 'Route::get')) {
            // Extract URI from route definition
            preg_match("/Route::get\('([^']*)'[,)]/", $line, $matches);
            if (isset($matches[1])) {
                $routeUris[] = $matches[1];
            }
        }
    }

    echo "\n=== Route URIs in order ===\n";
    foreach ($routeUris as $index => $uri) {
        $depth = empty($uri) ? 1 : substr_count($uri, '/') + 1;
        echo "{$index}: '{$uri}' (depth: {$depth})\n";
    }
    echo "=== End URIs ===\n";

    // Expected order by depth:
    // 1. '' (depth 1)
    // 2. 'create' (depth 2, static)
    // 3. '{id}' (depth 2, parameterized)
    // 4. 'admin/users' (depth 3, static)
    // 5. '{id}/edit' (depth 3, parameterized)
    // 6. 'admin/users/export/csv' (depth 4)

    expect($routeUris)->toHaveCount(6);
    expect($routeUris[0])->toBe(''); // depth 1
    expect($routeUris[1])->toBe('create'); // depth 2 static
    expect($routeUris[2])->toBe('{id}'); // depth 2 param
    expect($routeUris[3])->toBe('admin/users'); // depth 3 static
    expect($routeUris[4])->toBe('{id}/edit'); // depth 3 param
    expect($routeUris[5])->toBe('admin/users/export/csv'); // depth 4
});
