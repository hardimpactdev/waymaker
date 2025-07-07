<?php

namespace HardImpact\Waymaker;

use HardImpact\Waymaker\Commands\WaymakerCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WaymakerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('waymaker')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_wayfinder_routes_table')
            ->hasCommand(WaymakerCommand::class);
    }
}
