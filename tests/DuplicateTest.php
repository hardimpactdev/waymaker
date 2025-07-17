<?php

use HardImpact\Waymaker\Waymaker;

test('it handles multiple methods with the same HTTP verb and URI', function () {
    // Create a test controller with both index and show methods
    $testControllerPath = __DIR__.'/TestDuplicateController.php';
    file_put_contents($testControllerPath, '<?php
    namespace HardImpact\Waymaker\Tests;
    
    use Illuminate\Routing\Controller;
    use HardImpact\Waymaker\Get;
    
    class TestDuplicateController extends Controller
    {
        #[Get]
        public function index()
        {
            return "Index";
        }
        
        #[Get(uri: "{id}")]
        public function show()
        {
            return "Show";
        }
    }
    ');

    try {
        // Temporarily set controller path to our test location
        Waymaker::setControllerPath(
            __DIR__,
            'HardImpact\\Waymaker\\Tests'
        );

        $routes = Waymaker::generateRouteDefinitions();

        // Convert routes to string for easier inspection
        $routesString = implode("\n", $routes);

        // Check if methods generate routes with different URIs
        expect($routesString)->toContain("Route::get('/test-duplicate', [\\HardImpact\\Waymaker\\Tests\\TestDuplicateController::class, 'index'])")
            ->and($routesString)->toContain("Route::get('/test-duplicate/{id}', [\\HardImpact\\Waymaker\\Tests\\TestDuplicateController::class, 'show'])")
            ->and($routesString)->toContain('TestDuplicateController.index')
            ->and($routesString)->toContain('TestDuplicateController.show');
    } finally {
        // Clean up test file
        if (file_exists($testControllerPath)) {
            unlink($testControllerPath);
        }
    }
});
