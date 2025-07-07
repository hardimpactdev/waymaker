<?php

use HardImpact\Waymaker\Waymaker;
use HardImpact\Waymaker\Tests\Traits\TestFixtures;

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

    $expectedGetRoute = "Route::get('/articles/{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'show'])->name('Controllers.ArticleController.show')->middleware(['auth', 'verified']);";
    $expectedPostRoute = "Route::post('/articles', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'store'])->name('Controllers.ArticleController.store')->middleware(['auth', 'verified']);";
    $expectedPutRoute = "Route::put('/articles/{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'update'])->name('Controllers.ArticleController.update')->middleware(['auth', 'verified']);";
    $expectedPatchRoute = "Route::patch('/articles/{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'edit'])->name('Controllers.ArticleController.edit')->middleware(['auth', 'verified']);";
    $expectedDeleteRoute = "Route::delete('/articles/{article:slug}', [\\HardImpact\\Waymaker\\Tests\\Http\\Controllers\\ArticleController::class, 'destroy'])->name('Controllers.ArticleController.destroy')->middleware(['auth', 'verified']);";

    expect($routes)->toContain($expectedGetRoute);
    expect($routes)->toContain($expectedPostRoute);
    expect($routes)->toContain($expectedPutRoute);
    expect($routes)->toContain($expectedPatchRoute);
    expect($routes)->toContain($expectedDeleteRoute);
});

/**
 * Test direct route grouping logic
 */
test('it correctly groups routes by prefix', function () {
    // Get reflection method for testing
    $reflectionClass = new ReflectionClass(Waymaker::class);
    $flattenMethod = $reflectionClass->getMethod('flattenGroupedRoutes');
    $flattenMethod->setAccessible(true);

    // Create a sample grouped routes array
    $groupedRoutes = [
        'api' => [
            "Route::get('/api/users', [\\App\\Http\\Controllers\\UserController::class, 'index'])->name('api.users.index');",
            "Route::post('/api/users', [\\App\\Http\\Controllers\\UserController::class, 'store'])->name('api.users.store');",
        ],
        'admin' => [
            "Route::get('/admin/dashboard', [\\App\\Http\\Controllers\\Admin\\DashboardController::class, 'index'])->name('admin.dashboard');",
        ],
        '/' => [
            "Route::get('/', [\\App\\Http\\Controllers\\HomeController::class, 'index'])->name('home');",
        ],
    ];

    // Test flattening logic
    $flattened = $flattenMethod->invoke(null, $groupedRoutes);

    // Verify the flattened routes structure includes each group
    // and has the correct routes in each group

    // Convert to a string for easier searching
    $flattenedString = implode("\n", $flattened);

    // Check for groups
    expect($flattenedString)->toContain('// /api');
    expect($flattenedString)->toContain('// /admin');
    expect($flattenedString)->toContain('// /');

    // Check for routes
    expect($flattenedString)->toContain('/api/users');
    expect($flattenedString)->toContain('Route::get');
    expect($flattenedString)->toContain('Route::post');
    expect($flattenedString)->toContain('/admin/dashboard');

    // Test for the home route - the format might vary, so check for key components
    expect($flattenedString)->toContain('HomeController');
    expect($flattenedString)->toContain("'index'");
    expect($flattenedString)->toContain("'home'");

    // Check that there are blank lines between groups
    expect($flattenedString)->toContain("\n\n");
});
