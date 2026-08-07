<?php

use NckRtl\Waymaker\Tests\Traits\TestFixtures;
use NckRtl\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
    $this->setupWaymaker();
});

afterEach(function () {
    $this->tearDownFixtures();
});

/**
 * Test that routes are generated with proper grouping format.
 */
test('it generates routes with prefix and middleware grouping', function () {
    // Create multiple controllers with same prefix and middleware
    $controller1 = <<<'PHP'
<?php

namespace NckRtl\Waymaker\Tests\Http\Controllers\temp;

use NckRtl\Waymaker\Get;
use NckRtl\Waymaker\Post;

class GroupInspectionEntryController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
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
    
    #[Get(uri: 'create')]
    public function create()
    {
        return 'create';
    }
    
    #[Get(uri: '{inspection_entry}')]
    public function show($inspection_entry)
    {
        return 'show';
    }
    
    #[Get(uri: '{inspection_entry}/pdf')]
    public function pdf($inspection_entry)
    {
        return 'pdf';
    }
}
PHP;

    $controller2 = <<<'PHP'
<?php

namespace NckRtl\Waymaker\Tests\Http\Controllers\temp;

use NckRtl\Waymaker\Get;

class GroupInspectionExportController
{
    protected static string $routePrefix = 'inspections';
    protected static array $routeMiddleware = ['auth', 'active'];
    
    #[Get(uri: 'export')]
    public function export()
    {
        return 'export';
    }
}
PHP;

    file_put_contents($this->tempPath.'/GroupInspectionEntryController.php', $controller1);
    file_put_contents($this->tempPath.'/GroupInspectionExportController.php', $controller2);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Convert to string
    $routesString = implode("\n", $definitions);

    // Check for proper grouping format
    expect($routesString)->toContain("Route::prefix('inspections')->middleware(['auth', 'active'])->group(function (): void {");

    // Check that routes within group don't have leading slashes or repeated middleware
    expect($routesString)->toContain("Route::get('', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionEntryController::class, 'index'])->name('GroupInspectionEntryController.index');");
    expect($routesString)->toContain("Route::post('', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionEntryController::class, 'store'])->name('GroupInspectionEntryController.store');");
    expect($routesString)->toContain("Route::get('create', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionEntryController::class, 'create'])->name('GroupInspectionEntryController.create');");
    expect($routesString)->toContain("Route::get('export', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionExportController::class, 'export'])->name('GroupInspectionExportController.export');");
    expect($routesString)->toContain("Route::get('{inspection_entry}', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionEntryController::class, 'show'])->name('GroupInspectionEntryController.show');");
    expect($routesString)->toContain("Route::get('{inspection_entry}/pdf', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupInspectionEntryController::class, 'pdf'])->name('GroupInspectionEntryController.pdf');");

    // Check closing of group
    expect($routesString)->toContain('});');

    // Ensure no duplicate middleware on individual routes
    expect($routesString)->not->toContain("->middleware(['auth', 'active']);");
});

/**
 * Test mixed middleware scenarios.
 */
test('it handles mixed middleware scenarios correctly', function () {
    // Controller with group middleware
    $controller1 = <<<'PHP'
<?php

namespace NckRtl\Waymaker\Tests\Http\Controllers\temp;

use NckRtl\Waymaker\Get;

class AdminController
{
    protected static string $routePrefix = 'admin';
    protected static array $routeMiddleware = ['auth', 'admin'];
    
    #[Get]
    public function dashboard()
    {
        return 'dashboard';
    }
    
    #[Get(uri: 'users', middleware: 'can:manage-users')]
    public function users()
    {
        return 'users';
    }
}
PHP;

    // Controller with no middleware
    $controller2 = <<<'PHP'
<?php

namespace NckRtl\Waymaker\Tests\Http\Controllers\temp;

use NckRtl\Waymaker\Get;

class GroupPublicController
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

    file_put_contents($this->tempPath.'/AdminController.php', $controller1);
    file_put_contents($this->tempPath.'/GroupPublicController.php', $controller2);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $definitions);

    // Debug output
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Admin routes should be grouped with middleware
    expect($routesString)->toContain("Route::prefix('admin')->middleware(['auth', 'admin'])->group(function (): void {");

    // Route with additional middleware should have it appended
    expect($routesString)->toContain("Route::get('users', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\AdminController::class, 'users'])->name('AdminController.users')->middleware('can:manage-users');");

    // Public routes without middleware should not have a middleware group
    expect($routesString)->toContain("Route::get('/', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupPublicController::class, 'home'])->name('GroupPublicController.home');");
    expect($routesString)->toContain("Route::get('/group-public/about', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\GroupPublicController::class, 'about'])->name('GroupPublicController.about');");
});

/**
 * Test routes without prefix but with middleware.
 */
test('it handles routes without prefix but with middleware', function () {
    $controller = <<<'PHP'
<?php

namespace NckRtl\Waymaker\Tests\Http\Controllers\temp;

use NckRtl\Waymaker\Get;
use NckRtl\Waymaker\Post;

class AuthController
{
    protected static array $routeMiddleware = ['guest'];
    
    #[Get(uri: 'login')]
    public function showLogin()
    {
        return 'login form';
    }
    
    #[Post(uri: 'login')]
    public function login()
    {
        return 'process login';
    }
}
PHP;

    file_put_contents($this->tempPath.'/AuthController.php', $controller);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $definitions);

    // Debug output
    // echo "\nGenerated routes:\n" . $routesString . "\n\n";

    // Should create a middleware-only group (formatMiddleware returns 'guest' for single item)
    expect($routesString)->toContain("Route::middleware('guest')->group(function (): void {");
    expect($routesString)->toContain("Route::get('/auth/login', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\AuthController::class, 'showLogin'])->name('AuthController.showLogin');");
    expect($routesString)->toContain("Route::post('/auth/login', [\\NckRtl\\Waymaker\\Tests\\Http\\Controllers\\temp\\AuthController::class, 'login'])->name('AuthController.login');");
});
