<?php

namespace HardImpact\Waymaker\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use HardImpact\Waymaker\WaymakerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'HardImpact\\Waymaker\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            WaymakerServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Override app_path to point to our test directory
        $app->bind('path', function () {
            return __DIR__;
        });

        // Override app_path helper
        $app->bind('path.app', function () {
            return __DIR__;
        });

        // Override the app_path helper function
        $app->bind('app.path', function () {
            return __DIR__;
        });

        // Override the app namespace
        $app['config']->set('app.namespace', 'HardImpact\\Waymaker\\Tests');

        // Override the app_path helper function globally
        $app->bind('app_path', function () {
            return __DIR__;
        });

        // Override the app_path helper function with argument
        $app->bind('app_path.*', function ($app, $parameters) {
            return __DIR__.'/'.ltrim($parameters[0], '/');
        });

        // Set up test controllers
        $app['config']->set('inertia.testing.page_paths', [__DIR__.'/Controllers']);

        // Add our test controllers to the autoloader
        $app['config']->set('app.controllers', [
            __DIR__.'/Controllers',
        ]);
    }
}
