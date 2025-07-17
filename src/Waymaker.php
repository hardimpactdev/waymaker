<?php

namespace HardImpact\Waymaker;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\Finder\Exception\DirectoryNotFoundException;
use Symfony\Component\Finder\Finder;

class Waymaker
{
    private static ?string $controllerPath = null;

    private static ?string $controllerNamespace = null;

    /**
     * Load routes from the waymaker.php file.
     */
    public static function routes(): void
    {
        $routeFile = base_path('routes/waymaker.php');

        if (file_exists($routeFile)) {
            try {
                require $routeFile;
            } catch (\Throwable $e) {
                Log::error("Failed to load waymaker.php: {$e->getMessage()}");
            }
        }
    }

    /**
     * Set the controller path and namespace.
     *
     * @param  string|null  $path  The controller path (defaults to app_path('Http/Controllers') if null)
     * @param  string|null  $namespace  The controller namespace (defaults to 'App\\Http\\Controllers' if null)
     */
    public static function setControllerPath(?string $path, ?string $namespace = null): void
    {
        // Use realpath to resolve any relative paths to absolute paths
        self::$controllerPath = $path ? realpath($path) : app_path('Http/Controllers');
        self::$controllerNamespace = $namespace ?? 'App\\Http\\Controllers';
    }

    /**
     * Get the kebab-case name of a controller without the "Controller" suffix.
     *
     * @param  string  $controllerName  The controller name
     * @return string The kebab-cased name
     */
    private static function getControllerBaseName(string $controllerName): string
    {
        return Str::kebab(str_replace('Controller', '', $controllerName));
    }

    /**
     * Generate route definitions for all controllers.
     *
     * @return array<string> Array of route definition strings
     */
    public static function generateRouteDefinitions(): array
    {
        $cacheKey = 'waymaker.definitions';

        // Skip caching in test environment
        if (app()->environment('production') && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $groupedRoutes = [];
        $routeRegistry = []; // Track routes to detect duplicates

        $controllerPath = self::$controllerPath ?? app_path('Http/Controllers');
        $namespace = self::$controllerNamespace ?? 'App\\Http\\Controllers';

        try {
            $files = (new Finder)->files()->in($controllerPath)->name('*Controller.php');

            // Reset the iterator to use it again
            $files = (new Finder)->files()->in($controllerPath)->name('*Controller.php');
        } catch (DirectoryNotFoundException $e) {
            Log::error("Controller directory not found: {$controllerPath}");

            return [];
        }

        foreach ($files as $file) {
            // Get the relative path from the controller directory
            $relativePath = $file->getRelativePath();
            $filename = $file->getFilename();
            $className = pathinfo($filename, PATHINFO_FILENAME);

            // Build the class name including subdirectory namespace
            if ($relativePath) {
                // Convert directory separators to namespace separators
                $relativeNamespace = str_replace('/', '\\', $relativePath);
                $class = $namespace.'\\'.$relativeNamespace.'\\'.$className;
            } else {
                $class = $namespace.'\\'.$className;
            }

            if (! class_exists($class)) {
                continue;
            }

            self::processControllerClass($class, $groupedRoutes, $routeRegistry);
        }

        // Flatten the grouped definitions into a single array, with group comments
        $flattened = self::flattenGroupedRoutes($groupedRoutes);

        // Cache the result in production
        if (app()->environment('production')) {
            Cache::put($cacheKey, $flattened, now()->addMinutes(60));
        }

        return $flattened;
    }

    /**
     * Process a controller class to extract route definitions.
     *
     * @param  string  $class  The fully qualified controller class name
     * @param  array<string, array<string, mixed>>  &$groupedRoutes  Reference to the grouped routes array
     * @param  array<string, array<string, mixed>>  &$routeRegistry  Reference to the route registry
     */
    private static function processControllerClass(string $class, array &$groupedRoutes, array &$routeRegistry): void
    {
        try {
            $reflection = new ReflectionClass($class);

            $routePrefix = null;
            if ($reflection->hasProperty('routePrefix')) {
                $routePrefix = $reflection->getStaticPropertyValue('routePrefix');
            }

            $controllerMiddleware = [];
            if ($reflection->hasProperty('routeMiddleware')) {
                $middlewareValue = $reflection->getStaticPropertyValue('routeMiddleware');
                $controllerMiddleware = is_array($middlewareValue) ? $middlewareValue : [$middlewareValue];
            }

            // Get all public methods from the controller
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            $controllerMethods = [];

            // Filter out inherited methods
            foreach ($methods as $method) {
                if ($method->class === $class) {
                    $controllerMethods[] = $method;
                }
            }

            // Process each controller method
            foreach ($controllerMethods as $method) {
                self::processControllerMethod($method, $reflection, $class, $controllerMiddleware, $routePrefix, $groupedRoutes, $routeRegistry);
            }
        } catch (ReflectionException $e) {
            Log::error("Failed to reflect class {$class}: {$e->getMessage()}");
        }
    }

    /**
     * Process a controller method to extract route definitions.
     *
     * @param  ReflectionMethod  $method  The reflection method
     * @param  ReflectionClass  $reflection  The reflection class
     * @param  string  $class  The fully qualified controller class name
     * @param  array<string>  $controllerMiddleware  The controller middleware
     * @param  string|null  $routePrefix  The route prefix
     * @param  array<string, array<string, mixed>>  &$groupedRoutes  Reference to the grouped routes array
     * @param  array<string, array<string, mixed>>  &$routeRegistry  Reference to the route registry
     */
    private static function processControllerMethod(
        ReflectionMethod $method,
        ReflectionClass $reflection,
        string $class,
        array $controllerMiddleware,
        ?string $routePrefix,
        array &$groupedRoutes,
        array &$routeRegistry
    ): void {
        // Skip methods in parent class
        if ($method->class !== $class) {
            return;
        }

        // Skip constructor and other magic methods
        if ($method->isConstructor() || $method->isDestructor() || strpos($method->name, '__') === 0) {
            return;
        }

        // Look for any route attribute (Get, Post, Put, Patch, Delete)
        $routeAttr = null;
        foreach ($method->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof RouteAttribute) {
                $routeAttr = $instance;
                break;
            }
        }

        // Skip methods without route attributes
        if ($routeAttr === null) {
            return;
        }

        // Extract middleware from route attribute
        $routeMiddleware = [];
        if ($routeAttr->middleware !== null) {
            $routeMiddleware = is_string($routeAttr->middleware) ? [$routeAttr->middleware] : $routeAttr->middleware;
        }

        // Combine middleware, removing duplicates
        $combinedMiddleware = array_values(array_unique(array_merge($controllerMiddleware, $routeMiddleware)));

        // Get HTTP method from attribute
        $httpMethod = $routeAttr->method;
        $httpMethodValue = strtolower($httpMethod->value);

        // Generate URI and route name
        $uri = self::generateUri($routePrefix, $routeAttr->uri, $routeAttr->parameters, $reflection->getShortName(), $method->name);
        $routeName = self::generateRouteName($method->name, $routeAttr->name, $class);

        // Check for duplicate routes
        $routeKey = $httpMethodValue.':'.$uri;
        if (isset($routeRegistry[$routeKey])) {
            $existingRoute = $routeRegistry[$routeKey];
            throw new \RuntimeException(
                "Duplicate route detected: {$httpMethodValue} {$uri}\n".
                "First defined in: {$existingRoute['class']}::{$existingRoute['method']}\n".
                "Duplicate found in: {$class}::{$method->name}"
            );
        }

        // Register the route
        $routeRegistry[$routeKey] = [
            'class' => $class,
            'method' => $method->name,
            'uri' => $uri,
            'httpMethod' => $httpMethodValue,
        ];

        // Store route data instead of building definition immediately
        $routeData = [
            'method' => $httpMethodValue,
            'uri' => $uri,
            'class' => $class,
            'action' => $method->name,
            'name' => $routeName,
            'middleware' => $combinedMiddleware,
            'routeMiddleware' => $routeMiddleware, // Keep track of route-specific middleware
            'controllerMiddleware' => $controllerMiddleware, // Keep track of controller middleware
        ];

        // Group routes by prefix and middleware for organization
        $groupKey = self::generateGroupKey($routePrefix, $controllerMiddleware);

        // Initialize the group if it doesn't exist
        if (! isset($groupedRoutes[$groupKey])) {
            $groupedRoutes[$groupKey] = [
                'prefix' => $routePrefix,
                'middleware' => $controllerMiddleware,
                'routes' => [],
            ];
        }

        // Add the route data to the group
        $groupedRoutes[$groupKey]['routes'][] = $routeData;
    }

    /**
     * Generate a unique group key based on prefix and middleware.
     *
     * @param  string|null  $prefix  The route prefix
     * @param  array<string>  $middleware  The middleware array
     * @return string The group key
     */
    private static function generateGroupKey(?string $prefix, array $middleware): string
    {
        $prefix = $prefix ?? '/';
        $middlewareKey = empty($middleware) ? 'none' : implode(',', $middleware);

        return $prefix.'::'.$middlewareKey;
    }

    /**
     * Format middleware array into a string representation.
     *
     * @param  array<string>  $middleware  The middleware array
     * @return string Formatted middleware string
     */
    private static function formatMiddleware(array $middleware): string
    {
        if (count($middleware) === 1) {
            return "'".$middleware[0]."'";
        }

        return '[\''.implode("', '", $middleware).'\']';
    }

    /**
     * Generate URI for a route.
     *
     * @param  string|null  $prefix  The route prefix
     * @param  string|null  $customUri  Custom URI from route attribute
     * @param  array<string>|null  $parameters  Route parameters
     * @param  string  $controllerName  Controller name
     * @param  string  $methodName  Method name
     * @return string The generated URI
     */
    private static function generateUri(
        ?string $prefix,
        ?string $customUri,
        ?array $parameters,
        string $controllerName,
        string $methodName
    ): string {
        // Base URI from prefix or controller name
        if ($prefix) {
            $baseUri = '/'.trim($prefix, '/');
        } else {
            $baseUri = '/'.self::getControllerBaseName($controllerName);
        }

        // If custom URI is provided, append it to the base URI
        if ($customUri !== null) {
            // Special case: if custom URI is exactly '/', use root
            if ($customUri === '/') {
                $uri = '/';
            } else {
                // Remove leading slash from custom URI to avoid double slashes
                $customUri = ltrim($customUri, '/');
                // If custom URI is empty after trimming, use the base URI
                if ($customUri === '') {
                    $uri = $baseUri;
                } else {
                    $uri = rtrim($baseUri, '/').'/'.$customUri;
                }
            }

            // Don't apply any additional conventions when custom URI is provided
        } else {
            $uri = $baseUri;

            // Apply RESTful method conventions if no parameters are provided
            if (empty($parameters)) {
                // Methods that typically operate on individual resources
                if (in_array($methodName, ['show', 'edit', 'update', 'destroy'])) {
                    // Add {id} parameter for resource methods
                    $uri = rtrim($uri, '/').'/{id}';
                } elseif ($methodName !== 'index' && $methodName !== 'create' && $methodName !== 'store') {
                    // For non-standard methods, only append the method name if it's different from the controller base name
                    $methodKebab = Str::kebab($methodName);
                    $controllerBase = self::getControllerBaseName($controllerName);

                    // Only append method name if it's not already part of the controller base name
                    if ($methodKebab !== $controllerBase && ! str_ends_with($controllerBase, '-'.$methodKebab)) {
                        $uri = rtrim($uri, '/').'/'.Str::kebab($methodName);
                    }
                }
            }

            // Add parameters if present
            if ($parameters) {
                $wrappedParams = array_map(fn ($param) => '{'.$param.'}', $parameters);
                $uri = rtrim($uri, '/').'/'.implode('/', $wrappedParams);
            }
        }

        // Ensure the URI is properly formatted
        return trim($uri, '/') === '' ? '/' : $uri;
    }

    /**
     * Generate route name for a method.
     *
     * @param  string  $methodName  The method name
     * @param  string|null  $customName  Custom route name from attribute
     * @param  string  $controllerClass  Fully qualified controller class name
     * @return string The generated route name
     */
    private static function generateRouteName(string $methodName, ?string $customName, string $controllerClass): string
    {
        if ($customName) {
            return $customName;
        }

        // Extract the controller namespace path relative to the base namespace
        $baseNamespace = self::$controllerNamespace ?? 'App\\Http\\Controllers';
        $relativeClass = str_replace($baseNamespace.'\\', '', $controllerClass);

        // Replace namespace separators with dots
        $namespacePath = str_replace('\\', '.', $relativeClass);

        // Always use {NamespacePath}.{method} format unless a custom name is provided
        return sprintf('%s.%s', $namespacePath, $methodName);
    }

    /**
     * Flatten grouped routes into a single array with proper grouping.
     *
     * @param  array<string, array<string, mixed>>  $groupedRoutes  The grouped routes
     * @return array<string> Flattened route definitions
     */
    private static function flattenGroupedRoutes(array $groupedRoutes): array
    {
        $flattened = [];
        $isFirst = true;

        foreach ($groupedRoutes as $groupKey => $group) {
            // Add a blank line between groups (but not before the first group)
            if (! $isFirst) {
                $flattened[] = '';
            }
            $isFirst = false;

            /** @var string|null $prefix */
            $prefix = $group['prefix'] ?? null;
            /** @var array<string> $middleware */
            $middleware = $group['middleware'] ?? [];
            /** @var array<array<string, mixed>> $routes */
            $routes = $group['routes'] ?? [];

            // Sort routes within the group by specificity
            $sortedRoutes = self::sortRoutesBySpecificity($routes);

            // Check if we need a group
            $hasGroup = $prefix !== null || ! empty($middleware);

            // Generate the group based on what we have
            if ($prefix !== null && ! empty($middleware)) {
                // Both prefix and middleware
                $flattened[] = sprintf(
                    "Route::prefix('%s')->middleware(%s)->group(function () {",
                    trim($prefix, '/'),
                    self::formatMiddleware($middleware)
                );
            } elseif ($prefix !== null) {
                // Only prefix
                $flattened[] = sprintf(
                    "Route::prefix('%s')->group(function () {",
                    trim($prefix, '/')
                );
            } elseif (! empty($middleware)) {
                // Only middleware
                $flattened[] = sprintf(
                    'Route::middleware(%s)->group(function () {',
                    self::formatMiddleware($middleware)
                );
            }

            // Add routes (with indentation only if in a group)
            foreach ($sortedRoutes as $routeData) {
                if ($hasGroup) {
                    $flattened[] = '    '.self::buildRouteDefinition($routeData, $prefix !== null);
                } else {
                    $flattened[] = self::buildRouteDefinition($routeData, $prefix !== null);
                }
            }

            // Close group if we opened one
            if ($hasGroup) {
                $flattened[] = '});';
            }
        }

        return $flattened;
    }

    /**
     * Build a route definition string from route data.
     *
     * @param  array<string, mixed>  $routeData  The route data
     * @param  bool  $hasPrefix  Whether the route is within a prefix group
     * @return string The route definition
     */
    private static function buildRouteDefinition(array $routeData, bool $hasPrefix): string
    {
        $escapedClass = '\\'.ltrim($routeData['class'], '\\');

        // Adjust URI based on whether we're in a prefix group
        $uri = $routeData['uri'];
        if ($hasPrefix && $routeData['uri'] !== '/') {
            // Remove the prefix from the URI since it's handled by the group
            $prefixPattern = '/^\/[^\/]+/';
            $uri = preg_replace($prefixPattern, '', $uri);
            $uri = ltrim($uri, '/');
        }

        $definition = sprintf(
            "Route::%s('%s', [%s::class, '%s'])->name('%s')",
            $routeData['method'],
            $uri,
            $escapedClass,
            $routeData['action'],
            $routeData['name']
        );

        // Only add route-specific middleware (not controller middleware which is on the group)
        if (! empty($routeData['routeMiddleware'])) {
            $definition .= sprintf('->middleware(%s)', self::formatMiddleware($routeData['routeMiddleware']));
        }

        return $definition.';';
    }

    /**
     * Sort routes by specificity: depth first, then static routes before parameterized routes.
     *
     * @param  array<array<string, mixed>>  $routes  The routes to sort
     * @return array<array<string, mixed>> Sorted route definitions
     */
    private static function sortRoutesBySpecificity(array $routes): array
    {
        usort($routes, function ($a, $b) {
            // Now working with route data arrays instead of strings
            $uriA = $a['uri'];
            $uriB = $b['uri'];

            // Count segments (depth) - number of slashes
            $depthA = substr_count($uriA, '/');
            $depthB = substr_count($uriB, '/');

            // Primary sort: Routes with fewer segments (less depth) come first
            if ($depthA !== $depthB) {
                return $depthA - $depthB;
            }

            // Secondary sort: Within the same depth, count parameters
            $paramsA = substr_count($uriA, '{');
            $paramsB = substr_count($uriB, '{');

            // Routes with fewer parameters (more static) come first
            if ($paramsA !== $paramsB) {
                return $paramsA - $paramsB;
            }

            // Tertiary sort: Alphabetically for consistency
            return strcmp($uriA, $uriB);
        });

        return $routes;
    }
}
