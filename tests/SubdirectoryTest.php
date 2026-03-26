<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;
use Illuminate\Support\Facades\File;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
});

afterEach(function () {
    $this->tearDownFixtures();
});

test('it discovers controllers in subdirectories', function () {
    // Create a subdirectory structure with controllers
    $authPath = $this->tempPath.'/Auth';
    File::makeDirectory($authPath, 0777, true);

    // Create a controller in the root directory
    $homeControllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class SubdirHomeController
{
    #[Get]
    public function index()
    {
        return 'home';
    }
}
PHP;

    file_put_contents($this->tempPath.'/SubdirHomeController.php', $homeControllerContent);

    // Create a controller in the Auth subdirectory
    $loginControllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp\Auth;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class LoginController
{
    #[Get(uri: '/login')]
    public function showLoginForm()
    {
        return 'login form';
    }
    
    #[Post(uri: '/login')]
    public function login()
    {
        return 'processing login';
    }
}
PHP;

    file_put_contents($authPath.'/LoginController.php', $loginControllerContent);

    $this->setupWaymaker();

    $routes = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $routes);

    // Debug
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Check that both controllers are discovered
    expect($routesString)->toContain('SubdirHomeController');
    expect($routesString)->toContain('Auth\\LoginController');

    // Check specific routes (note: SubdirHomeController generates /home not / by default)
    expect($routesString)->toContain("Route::get('/subdir-home', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\SubdirHomeController::class, 'index'])");
    expect($routesString)->toContain("Route::get('/login', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\Auth\\LoginController::class, 'showLoginForm'])");
    expect($routesString)->toContain("Route::post('/login', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\Auth\\LoginController::class, 'login'])");
});

test('it generates correct fully qualified class names for controllers in subdirectories', function () {
    // Create a Settings subdirectory
    $settingsPath = $this->tempPath.'/Settings';
    File::makeDirectory($settingsPath, 0777, true);

    // Create a controller in the Settings subdirectory
    $profileControllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp\Settings;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Put;

class SubdirProfileController
{
    protected static array $routeMiddleware = ['auth'];
    
    #[Get]
    public function edit()
    {
        return 'edit profile';
    }
    
    #[Put]
    public function update()
    {
        return 'update profile';
    }
}
PHP;

    file_put_contents($settingsPath.'/SubdirProfileController.php', $profileControllerContent);

    $this->setupWaymaker();

    $routes = Waymaker::generateRouteDefinitions();

    // Find routes for the SubdirProfileController
    $profileRoutes = array_filter($routes, function ($route) {
        return str_contains($route, 'SubdirProfileController');
    });

    expect($profileRoutes)->not->toBeEmpty();

    foreach ($profileRoutes as $route) {
        // Check that the fully qualified class name includes the subdirectory
        expect($route)->toContain('\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\Settings\\SubdirProfileController');
    }

    // Check that middleware is applied at the group level
    $routesString = implode("\n", $routes);
    expect($routesString)->toContain("Route::middleware('auth')->group(function (): void {");
});

test('it handles deeply nested controller directories', function () {
    // Create a deeply nested controller structure
    $deepPath = $this->tempPath.'/Admin/Reports/Financial';
    File::makeDirectory($deepPath, 0777, true);

    // Create a controller in the deep directory
    $revenueControllerContent = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp\Admin\Reports\Financial;

use HardImpact\Waymaker\Get;

class SubdirRevenueController
{
    protected static string $routePrefix = 'admin/reports/financial/revenue';
    
    #[Get]
    public function index()
    {
        return 'revenue report';
    }
    
    #[Get(uri: 'monthly')]
    public function monthly()
    {
        return 'monthly revenue';
    }
    
    #[Get(name: 'custom.revenue.show')]
    public function show()
    {
        return 'show revenue';
    }
}
PHP;

    file_put_contents($deepPath.'/SubdirRevenueController.php', $revenueControllerContent);

    $this->setupWaymaker();

    $routes = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $routes);

    // Debug: print the actual routes
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Check that the deeply nested controller is discovered
    expect($routesString)->toContain('Admin\\Reports\\Financial\\SubdirRevenueController');
    // Routes are grouped by prefix, so the prefix appears in the group statement
    expect($routesString)->toContain("Route::prefix('admin/reports/financial/revenue')");
    // Within the group, URIs are relative (prefix first segment stripped)
    expect($routesString)->toContain("'reports/financial/revenue/monthly'");
});
