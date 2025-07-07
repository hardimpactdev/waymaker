<?php

namespace HardImpact\Waymaker\Tests\Controllers;

use HardImpact\Waymaker\Waymaker;

/**
 * This class serves as a registry for test controllers.
 * It makes it easier to organize and run tests with specific controllers.
 */
class TestControllerRegistry
{
    /**
     * Register a controller from fixtures for testing.
     *
     * @param  string  $fixtureType  The type of fixture (e.g., 'RouteGeneration', 'MethodDefaults')
     * @param  string  $controllerName  The name of the controller class
     */
    public static function registerControllerFromFixture(string $fixtureType, string $controllerName): void
    {
        $path = __DIR__.'/../Fixtures/Controllers/'.$fixtureType;

        // Register the controller path
        Waymaker::setControllerPath(
            $path,
            'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp'
        );
    }

    /**
     * Register RouteGeneration controllers.
     */
    public static function registerRouteGenerationControllers(): void
    {
        self::registerControllerFromFixture('RouteGeneration', 'ArticleController');
    }

    /**
     * Register MethodDefaults controllers.
     */
    public static function registerMethodDefaultsControllers(): void
    {
        self::registerControllerFromFixture('MethodDefaults', 'MethodDefaultsTestController');
    }

    /**
     * Register Middleware controllers.
     */
    public static function registerMiddlewareControllers(): void
    {
        self::registerControllerFromFixture('Middleware', 'MiddlewareTestController');
    }
}
