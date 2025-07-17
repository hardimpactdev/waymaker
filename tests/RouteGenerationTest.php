<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
});

afterEach(function () {
    $this->tearDownFixtures();
});

/**
 * Test route generation from existing controller fixture
 */
test('it generates correct route definitions from controllers', function () {
    // Use the permanent fixture for this test
    Waymaker::setControllerPath(
        __DIR__.'/Http/Controllers',
        'HardImpact\\Waymaker\\Tests\\Http\\Controllers'
    );

    $routes = Waymaker::generateRouteDefinitions();

    // The routes should be within a group with prefix and middleware
    $expectedGroupStart = "Route::prefix('articles')->middleware(['auth', 'verified'])->group(function () {";
    $expectedShowRoute = "Route::get('{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'show'])->name('ArticleController.show');";
    $expectedStoreRoute = "Route::post('', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'store'])->name('ArticleController.store');";
    $expectedUpdateRoute = "Route::put('{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'update'])->name('ArticleController.update');";
    $expectedEditRoute = "Route::patch('{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'edit'])->name('ArticleController.edit');";
    $expectedDestroyRoute = "Route::delete('{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'destroy'])->name('ArticleController.destroy');";

    expect($routes)->toContain($expectedGroupStart);
    expect($routes)->toContain('    '.$expectedShowRoute);
    expect($routes)->toContain('    '.$expectedStoreRoute);
    expect($routes)->toContain('    '.$expectedUpdateRoute);
    expect($routes)->toContain('    '.$expectedEditRoute);
    expect($routes)->toContain('    '.$expectedDestroyRoute);
});

/**
 * Test direct route grouping logic
 */
test('it correctly groups routes by prefix', function () {
    // Get reflection method for testing
    $reflectionClass = new ReflectionClass(Waymaker::class);
    $flattenMethod = $reflectionClass->getMethod('flattenGroupedRoutes');
    $flattenMethod->setAccessible(true);

    // Create a sample grouped routes array with the correct structure
    $groupedRoutes = [
        'api::none' => [
            'prefix' => 'api',
            'middleware' => [],
            'routes' => [
                [
                    'method' => 'get',
                    'uri' => '/api/users',
                    'class' => 'App\\Http\\Controllers\\UserController',
                    'action' => 'index',
                    'name' => 'api.users.index',
                    'middleware' => [],
                    'routeMiddleware' => [],
                    'controllerMiddleware' => [],
                ],
                [
                    'method' => 'post',
                    'uri' => '/api/users',
                    'class' => 'App\\Http\\Controllers\\UserController',
                    'action' => 'store',
                    'name' => 'api.users.store',
                    'middleware' => [],
                    'routeMiddleware' => [],
                    'controllerMiddleware' => [],
                ],
            ],
        ],
        'admin::none' => [
            'prefix' => 'admin',
            'middleware' => [],
            'routes' => [
                [
                    'method' => 'get',
                    'uri' => '/admin/dashboard',
                    'class' => 'App\\Http\\Controllers\\Admin\\DashboardController',
                    'action' => 'index',
                    'name' => 'admin.dashboard',
                    'middleware' => [],
                    'routeMiddleware' => [],
                    'controllerMiddleware' => [],
                ],
            ],
        ],
        '/::none' => [
            'prefix' => null,
            'middleware' => [],
            'routes' => [
                [
                    'method' => 'get',
                    'uri' => '/',
                    'class' => 'App\\Http\\Controllers\\HomeController',
                    'action' => 'index',
                    'name' => 'home',
                    'middleware' => [],
                    'routeMiddleware' => [],
                    'controllerMiddleware' => [],
                ],
            ],
        ],
    ];

    // Test flattening logic
    $flattened = $flattenMethod->invoke(null, $groupedRoutes);

    // Convert to a string for easier searching
    $flattenedString = implode("\n", $flattened);

    // Check for grouped routes
    expect($flattenedString)->toContain("Route::prefix('api')->group(function () {");
    expect($flattenedString)->toContain("Route::prefix('admin')->group(function () {");

    // Check for routes
    expect($flattenedString)->toContain('UserController');
    expect($flattenedString)->toContain('Route::get');
    expect($flattenedString)->toContain('Route::post');
    expect($flattenedString)->toContain('DashboardController');

    // Test for the home route - should not be in a group
    expect($flattenedString)->toContain('HomeController');
    expect($flattenedString)->toContain("'index'");
    expect($flattenedString)->toContain("'home'");

    // Check that there are blank lines between groups
    expect($flattenedString)->toContain("\n\n");
});
