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

test('generates root route for invokable controller without prefix', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class HomeController
{
    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Home');
    }
}
PHP;
    file_put_contents($this->tempPath.'/HomeController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\HomeController::class, '__invoke'])->name('HomeController');";
    expect($routes)->toContain($expectedRoute);
});

test('generates prefixed route for invokable controller', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class ContactController
{
    protected static string $routePrefix = 'contact';
    protected static string $routeMiddleware = 'guest';

    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Contact');
    }
}
PHP;
    file_put_contents($this->tempPath.'/ContactController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    // Should be in a group with prefix
    $expectedGroupStart = "Route::prefix('contact')->middleware('guest')->group(function () {";
    $expectedRoute = "    Route::get('contact', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\ContactController::class, '__invoke'])->name('ContactController');";

    expect($routes)->toContain($expectedGroupStart);
    expect($routes)->toContain($expectedRoute);
});

test('generates custom URI route for invokable controller', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class AboutController
{
    #[Get(uri: 'about-us')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('About');
    }
}
PHP;
    file_put_contents($this->tempPath.'/AboutController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('about-us', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\AboutController::class, '__invoke'])->name('AboutController');";
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller route name excludes __invoke suffix', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class TestInvokableController
{
    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Test');
    }
}
PHP;
    file_put_contents($this->tempPath.'/TestInvokableController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    // Should NOT contain __invoke in the name
    $routesString = implode("\n", $routes);
    expect($routesString)->not->toContain("__invoke');");
    expect($routesString)->toContain("->name('TestInvokableController');");
});

test('invokable controller with custom route name', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class CustomNameController
{
    #[Get(name: 'custom.route.name')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Custom');
    }
}
PHP;
    file_put_contents($this->tempPath.'/CustomNameController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\CustomNameController::class, '__invoke'])->name('custom.route.name');";
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller with parameters', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class ShowItemController
{
    #[Get(parameters: ['id'])]
    public function __invoke(int $id): \Inertia\Response
    {
        return inertia('Item');
    }
}
PHP;
    file_put_contents($this->tempPath.'/ShowItemController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('{id}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\ShowItemController::class, '__invoke'])->name('ShowItemController');";
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller with prefix and custom URI', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class PrefixedCustomController
{
    protected static string $routePrefix = 'admin';

    #[Get(uri: 'custom-path')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('AdminCustom');
    }
}
PHP;
    file_put_contents($this->tempPath.'/PrefixedCustomController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    // When both prefix and custom URI are set, it creates a group with prefix
    // and the route uses the custom URI
    $expectedGroupStart = "Route::prefix('admin')->group(function () {";
    $expectedRoute = "    Route::get('custom-path', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\PrefixedCustomController::class, '__invoke'])->name('PrefixedCustomController');";

    expect($routes)->toContain($expectedGroupStart);
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller with middleware attribute', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class ProtectedController
{
    #[Get(middleware: 'auth')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Protected');
    }
}
PHP;
    file_put_contents($this->tempPath.'/ProtectedController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\ProtectedController::class, '__invoke'])->name('ProtectedController')->middleware('auth');";
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller with root URI', function () {
    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class RootController
{
    #[Get(uri: '/')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Root');
    }
}
PHP;
    file_put_contents($this->tempPath.'/RootController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedRoute = "Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\RootController::class, '__invoke'])->name('RootController');";
    expect($routes)->toContain($expectedRoute);
});

test('namespaced invokable controller generates correct route name', function () {
    // Create subdirectory
    $subDir = $this->tempPath.'/Admin';
    if (! is_dir($subDir)) {
        mkdir($subDir, 0777, true);
    }

    $controller = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp\Admin;

use HardImpact\Waymaker\Get;

class DashboardController
{
    protected static string $routePrefix = 'dashboard';

    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Admin/Dashboard');
    }
}
PHP;
    file_put_contents($subDir.'/DashboardController.php', $controller);

    $routes = Waymaker::generateRouteDefinitions();

    $expectedGroupStart = "Route::prefix('dashboard')->group(function () {";
    $expectedRoute = "    Route::get('dashboard', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\Admin\\DashboardController::class, '__invoke'])->name('Admin.DashboardController');";

    expect($routes)->toContain($expectedGroupStart);
    expect($routes)->toContain($expectedRoute);
});

test('invokable controller uses different HTTP methods', function () {
    $getController = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class GetInvokableController
{
    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Get');
    }
}
PHP;
    file_put_contents($this->tempPath.'/GetInvokableController.php', $getController);

    $postController = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Post;

class PostInvokableController
{
    #[Post]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Post');
    }
}
PHP;
    file_put_contents($this->tempPath.'/PostInvokableController.php', $postController);

    $routes = Waymaker::generateRouteDefinitions();

    expect($routes)->toContain("Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\GetInvokableController::class, '__invoke'])->name('GetInvokableController');");
    expect($routes)->toContain("Route::post('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\PostInvokableController::class, '__invoke'])->name('PostInvokableController');");
});

test('regular controllers still work alongside invokable controllers', function () {
    // Create an invokable controller
    $invokableController = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class SimpleInvokableController
{
    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Simple');
    }
}
PHP;
    file_put_contents($this->tempPath.'/SimpleInvokableController.php', $invokableController);

    // Create a regular controller with multiple methods
    $regularController = <<<'PHP'
<?php
namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class RegularController
{
    protected static string $routePrefix = 'regular';

    #[Get]
    public function index(): \Inertia\Response
    {
        return inertia('Regular/Index');
    }

    #[Get(uri: '{id}')]
    public function show(int $id): \Inertia\Response
    {
        return inertia('Regular/Show');
    }
}
PHP;
    file_put_contents($this->tempPath.'/RegularController.php', $regularController);

    $routes = Waymaker::generateRouteDefinitions();

    // Invokable should be root route
    expect($routes)->toContain("Route::get('/', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\SimpleInvokableController::class, '__invoke'])->name('SimpleInvokableController');");

    // Regular controller should be grouped
    expect($routes)->toContain("Route::prefix('regular')->group(function () {");
    expect($routes)->toContain("    Route::get('', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\RegularController::class, 'index'])->name('RegularController.index');");
    expect($routes)->toContain("    Route::get('{id}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp\\RegularController::class, 'show'])->name('RegularController.show');");
});
