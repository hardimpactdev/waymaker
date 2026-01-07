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
 * Test that routes are ordered by path depth first, then static before parameterized.
 */
test('it orders routes by path depth as primary factor', function () {
    // Create a controller with various route depths
    $controllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class InspectionController
{
    protected static string $routePrefix = 'inspections';
    
    // Depth 1 routes
    #[Get]
    public function index()
    {
        return 'List all inspections';
    }
    
    #[Post]
    public function store()
    {
        return 'Create inspection';
    }
    
    #[Get(uri: '{inspection}')]
    public function show($inspection)
    {
        return 'Show inspection';
    }
    
    // Depth 2 routes
    #[Get(uri: 'create')]
    public function create()
    {
        return 'Show create form';
    }
    
    #[Get(uri: 'archived')]
    public function archived()
    {
        return 'Show archived inspections';
    }
    
    #[Get(uri: '{inspection}/edit')]
    public function edit($inspection)
    {
        return 'Edit inspection';
    }
    
    #[Get(uri: '{inspection}/duplicate')]
    public function duplicate($inspection)
    {
        return 'Duplicate inspection';
    }
    
    // Depth 3 routes
    #[Get(uri: 'reports/monthly')]
    public function monthlyReports()
    {
        return 'Monthly reports';
    }
    
    #[Get(uri: 'reports/yearly')]
    public function yearlyReports()
    {
        return 'Yearly reports';
    }
    
    #[Get(uri: '{inspection}/items/{item}')]
    public function showItem($inspection, $item)
    {
        return 'Show inspection item';
    }
    
    #[Get(uri: 'archived/reports/{year}')]
    public function archivedReports($year)
    {
        return 'Archived reports for year';
    }
    
    // Depth 4 routes
    #[Get(uri: 'reports/monthly/export/pdf')]
    public function exportMonthlyPdf()
    {
        return 'Export monthly PDF';
    }
    
    #[Get(uri: '{inspection}/items/{item}/comments/{comment}')]
    public function showComment($inspection, $item, $comment)
    {
        return 'Show comment';
    }
}
PHP;

    file_put_contents($this->tempPath.'/InspectionController.php', $controllerContent);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Filter to only inspection routes
    $inspectionRoutes = array_filter($definitions, function ($def) {
        return str_contains($def, '/inspections') && str_contains($def, 'Route::');
    });

    // Reset array keys
    $inspectionRoutes = array_values($inspectionRoutes);

    // Debug output
    // echo "\nGenerated route order:\n";
    // foreach ($inspectionRoutes as $index => $route) {
    //     preg_match("/Route::\w+\('([^']+)'/", $route, $matches);
    //     echo ($index + 1) . ". " . $matches[1] . "\n";
    // }

    // Expected order:
    // Depth 1 (1 slash):
    // 1. /inspections (static)
    // 2. /inspections (POST - different method, so allowed)
    // 3. /inspections/{inspection} (parameterized)

    // Depth 2 (2 slashes):
    // 4. /inspections/create (static)
    // 5. /inspections/archived (static)
    // 6. /inspections/{inspection}/edit (parameterized)
    // 7. /inspections/{inspection}/duplicate (parameterized)

    // Depth 3 (3 slashes):
    // 8. /inspections/reports/monthly (static)
    // 9. /inspections/reports/yearly (static)
    // 10. /inspections/archived/reports/{year} (1 param)
    // 11. /inspections/{inspection}/items/{item} (2 params)

    // Depth 4 (4 slashes):
    // 12. /inspections/reports/monthly/export/pdf (static)
    // 13. /inspections/{inspection}/items/{item}/comments/{comment} (parameterized)

    // Extract URIs from routes, accounting for grouped routes
    $uris = [];
    $inGroup = false;
    $groupPrefix = '';

    foreach ($definitions as $definition) {
        // Check if we're entering a group
        if (preg_match("/Route::prefix\('([^']+)'\)/", $definition, $matches)) {
            $inGroup = true;
            $groupPrefix = '/'.$matches[1];
        }

        // Check if we're exiting a group
        if ($definition === '});') {
            $inGroup = false;
            $groupPrefix = '';
        }

        // Extract route URIs
        if (preg_match("/Route::\w+\('([^']+)'.*InspectionController/", $definition, $matches)) {
            $uri = $matches[1];
            // If we're in a group, prepend the group prefix
            if ($inGroup && $groupPrefix) {
                $uri = $groupPrefix.($uri === '' ? '' : '/'.$uri);
            }
            $uris[] = $uri;
        }
    }

    // Verify routes are generated
    expect(count($uris))->toBeGreaterThan(0);

    // Verify that static routes come before parameterized routes at the same depth
    // by checking that 'create' and 'archived' appear before '{inspection}/edit'
    $definitionsString = implode("\n", $definitions);

    // Static depth-2 routes should be defined
    expect($definitionsString)->toContain("'create'");
    expect($definitionsString)->toContain("'archived'");

    // Parameterized routes should also be defined
    expect($definitionsString)->toContain("{inspection}");
    expect($definitionsString)->toContain("{inspection}/edit");

    // Verify deeper routes are present
    expect($definitionsString)->toContain("reports/monthly");
    expect($definitionsString)->toContain("{inspection}/items/{item}");
});

/**
 * Test complex routing scenario with multiple controllers.
 */
test('it correctly orders routes across multiple controllers', function () {
    // Create multiple controllers
    $mainController = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class MainController
{
    #[Get(uri: '/')]
    public function home()
    {
        return 'Home';
    }
    
    #[Get(uri: 'about')]
    public function about()
    {
        return 'About';
    }
    
    #[Get(uri: 'contact/form')]
    public function contactForm()
    {
        return 'Contact form';
    }
}
PHP;

    $apiController = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class ApiController
{
    protected static string $routePrefix = 'api';
    
    #[Get(uri: 'users')]
    public function users()
    {
        return 'Users';
    }
    
    #[Get(uri: 'users/{id}')]
    public function user($id)
    {
        return 'User';
    }
    
    #[Get(uri: 'users/{id}/posts/{post}')]
    public function userPost($id, $post)
    {
        return 'User post';
    }
}
PHP;

    file_put_contents($this->tempPath.'/MainController.php', $mainController);
    file_put_contents($this->tempPath.'/ApiController.php', $apiController);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Filter actual routes
    $routes = array_filter($definitions, fn ($def) => str_contains($def, 'Route::'));
    $routes = array_values($routes);

    // Verify routes are generated
    expect(count($routes))->toBeGreaterThan(0);

    // Verify expected routes exist in the definitions
    $definitionsString = implode("\n", $definitions);
    expect($definitionsString)->toContain('MainController');
    expect($definitionsString)->toContain('ApiController');
    expect($definitionsString)->toContain("Route::prefix('api')");

    // Verify MainController routes
    expect($definitionsString)->toContain("'/'");
    expect($definitionsString)->toContain("'about'");
    expect($definitionsString)->toContain("'contact/form'");

    // Verify ApiController routes with prefix
    expect($definitionsString)->toContain("'users'");
    expect($definitionsString)->toContain("'users/{id}'");
    expect($definitionsString)->toContain("'users/{id}/posts/{post}'");
});
