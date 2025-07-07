<?php

namespace HardImpact\Waymaker\Tests\Factories;

use HardImpact\Waymaker\Enums\HttpMethod;
use Illuminate\Support\Facades\File;

class ControllerFactory
{
    private string $namespace = 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp';

    private string $className;

    private ?string $routePrefix = null;

    private array $routeMiddleware = [];

    private array $methods = [];

    /**
     * Create a new controller factory instance.
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * Create a new controller factory for the given class name.
     */
    public static function create(string $className): self
    {
        return new self($className);
    }

    /**
     * Set the controller namespace.
     */
    public function withNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Set the route prefix for the controller.
     */
    public function withRoutePrefix(?string $prefix): self
    {
        $this->routePrefix = $prefix;

        return $this;
    }

    /**
     * Set the route middleware for the controller.
     */
    public function withMiddleware(array|string $middleware): self
    {
        $this->routeMiddleware = is_array($middleware) ? $middleware : [$middleware];

        return $this;
    }

    /**
     * Add a method to the controller.
     */
    public function addMethod(
        string $name,
        ?HttpMethod $httpMethod = null,
        ?array $parameters = null,
        array|string|null $middleware = null,
        ?string $routeName = null,
        ?string $uri = null
    ): self {
        $this->methods[$name] = [
            'httpMethod' => $httpMethod,
            'parameters' => $parameters,
            'middleware' => $middleware,
            'routeName' => $routeName,
            'uri' => $uri,
        ];

        return $this;
    }

    /**
     * Generate the controller class code.
     */
    public function generate(): string
    {
        $code = "<?php\n\n";
        $code .= "namespace {$this->namespace};\n\n";
        $code .= "use Illuminate\\Routing\\Controller;\n";
        $code .= "use Inertia\\Response;\n";
        $code .= "use HardImpact\\Waymaker\\{Delete, Get, Patch, Post, Put};\n";
        $code .= "use HardImpact\\Waymaker\\Enums\\HttpMethod;\n\n";

        $code .= "class {$this->className} extends Controller\n{\n";

        // Add static properties
        if ($this->routePrefix !== null) {
            $code .= "    protected static string \$routePrefix = '{$this->routePrefix}';\n";
        }

        if (! empty($this->routeMiddleware)) {
            if (count($this->routeMiddleware) === 1) {
                $code .= "    protected static string \$routeMiddleware = '{$this->routeMiddleware[0]}';\n";
            } else {
                $middleware = "['".implode("', '", $this->routeMiddleware)."']";
                $code .= "    protected static array \$routeMiddleware = {$middleware};\n";
            }
        }

        if (! empty($this->routePrefix) || ! empty($this->routeMiddleware)) {
            $code .= "\n";
        }

        // Add methods
        foreach ($this->methods as $methodName => $config) {
            // Add method attribute if needed
            if ($config['httpMethod'] !== null || $config['parameters'] !== null ||
                $config['middleware'] !== null || $config['routeName'] !== null ||
                $config['uri'] !== null) {

                $httpMethod = $config['httpMethod'];
                $attributeName = match ($httpMethod?->name) {
                    'GET' => 'Get',
                    'POST' => 'Post',
                    'PUT' => 'Put',
                    'PATCH' => 'Patch',
                    'DELETE' => 'Delete',
                    default => null
                };

                if ($attributeName !== null || $config['parameters'] !== null ||
                    $config['middleware'] !== null || $config['routeName'] !== null ||
                    $config['uri'] !== null) {

                    $attributeParts = [];

                    if ($config['parameters'] !== null) {
                        $params = "['".implode("', '", $config['parameters'])."']";
                        $attributeParts[] = "parameters: {$params}";
                    }

                    if ($config['middleware'] !== null) {
                        if (is_array($config['middleware'])) {
                            if (count($config['middleware']) === 1) {
                                $attributeParts[] = "middleware: '{$config['middleware'][0]}'";
                            } else {
                                $middleware = "['".implode("', '", $config['middleware'])."']";
                                $attributeParts[] = "middleware: {$middleware}";
                            }
                        } else {
                            $attributeParts[] = "middleware: '{$config['middleware']}'";
                        }
                    }

                    if ($config['routeName'] !== null) {
                        $attributeParts[] = "name: '{$config['routeName']}'";
                    }

                    if ($config['uri'] !== null) {
                        $attributeParts[] = "uri: '{$config['uri']}'";
                    }

                    if ($attributeName !== null) {
                        $attributes = implode(', ', $attributeParts);
                        $code .= "    #[{$attributeName}({$attributes})]\n";
                    }
                }
            }

            // Add method signature and body
            $hasParameters = ! empty($config['parameters']);
            $paramSignature = $hasParameters ? '$param' : '';

            $code .= "    public function {$methodName}({$paramSignature}): Response\n";
            $code .= "    {\n";

            $viewName = ucfirst(str_replace('_', '/', $methodName));
            if ($hasParameters) {
                $code .= "        return inertia('{$viewName}', [\n";
                $code .= "            'param' => \$param,\n";
                $code .= "        ]);\n";
            } else {
                $code .= "        return inertia('{$viewName}');\n";
            }

            $code .= "    }\n\n";
        }

        $code = rtrim($code, "\n");
        $code .= "\n}\n";

        return $code;
    }

    /**
     * Save the generated controller to the specified path.
     */
    public function save(string $path): string
    {
        $fullPath = rtrim($path, '/').'/'.$this->className.'.php';
        File::put($fullPath, $this->generate());

        return $fullPath;
    }
}
