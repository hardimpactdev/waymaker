<?php

namespace HardImpact\Waymaker\Tests\Traits;

use HardImpact\Waymaker\Waymaker;

trait RouteAssertions
{
    /**
     * Get the test namespace for controllers.
     */
    protected function getTestNamespace(): string
    {
        return 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp';
    }

    /**
     * Assert that a route with the given properties exists in the generated routes.
     */
    protected function assertRouteExists(
        string $httpMethod,
        string $uri,
        string $controller,
        string $action,
        ?string $name = null,
        ?array $middleware = null
    ): void {
        $routes = Waymaker::generateRouteDefinitions();
        $routeName = $name ?? "Controllers.{$controller}.{$action}";

        // Build the expected route pattern
        $pattern = $this->buildExpectedRoutePattern(
            $httpMethod,
            $uri,
            $controller,
            $action,
            $routeName,
            $middleware
        );

        // Look for a route that matches our pattern
        $found = false;
        foreach ($routes as $route) {
            if (preg_match($pattern, $route)) {
                $found = true;
                break;
            }
        }

        expect($found)->toBeTrue("No route found matching: {$pattern}");
    }

    /**
     * Build a route definition regex pattern for testing.
     */
    protected function buildExpectedRoutePattern(
        string $httpMethod,
        string $uri,
        string $controller,
        string $action,
        string $name,
        ?array $middleware = null
    ): string {
        $namespace = $this->getTestNamespace();
        $lowercaseMethod = strtolower($httpMethod);

        $pattern = "/^Route::{$lowercaseMethod}\\('".preg_quote($uri, '/')."', \\[\\\\".preg_quote($namespace, '/')."\\\\{$controller}::class, '{$action}'\\]\\)->name\\('".preg_quote($name, '/')."'\\)";

        if ($middleware) {
            if (count($middleware) === 1) {
                $pattern .= "->middleware\\('".preg_quote($middleware[0], '/')."'\\)";
            } else {
                $middlewarePattern = implode("', '", array_map(function ($m) {
                    return preg_quote($m, '/');
                }, $middleware));
                $pattern .= "->middleware\\(\\['$middlewarePattern'\\]\\)";
            }
        }

        $pattern .= ';$/';

        return $pattern;
    }

    /**
     * Generate routes and get them.
     */
    protected function getGeneratedRoutes(): array
    {
        return Waymaker::generateRouteDefinitions();
    }
}
