# Waymaker

This Laravel packages lets you generate a routes file based on your public controller methods. This package works particularly well with Laravel Wayfinder, as it allows you to reference controller methods instead of just routes. Based on the method signature in your controllers we could generate a routes file, automating route management entirely.

## Installation

You can install the package via composer:

```bash
composer require nckrtl/waymaker
```

## Requirements

- PHP 8.4+
- Laravel 10, 11, or 12
- [vite-plugin-run](https://www.npmjs.com/package/vite-plugin-run) (for automatic route generation during development)

Install the vite plugin:

```bash
npm install -D vite-plugin-run
```

## Usage

Update your vite config to include an additional run command:

```ts
import { run } from "vite-plugin-run";

export default defineConfig({
    plugins: [
        run([
            {
                name: "waymaker",
                run: ["php", "artisan", "waymaker:generate"],
                pattern: ["app/**/Http/**/*.php"],
            },
        ]),
    ],
});
```

Next, update your main routes file to include the generated routes with:

```php
use HardImpact\Waymaker\Facades\Waymaker;

Waymaker::routes();
```

Now you're all set. Running vite dev should now generate the routes based on your controller methods. On file change of any controller the routes file will be regenerated.

### Route definition structure

The way routes are generated are pretty opionated. The naming convention of routes is inspired by how Laravel Wayfinder exposes routes/actions.

**Important:** As of version 2.0, explicit route attributes are required on all controller methods that should generate routes. Methods without route attributes will be ignored.

For this controller:

```php
<?php

namespace App\Http\Controllers;

use HardImpact\Waymaker\Get;

class ContactController extends Controller
{
    #[Get(uri: '{id}')]
    public function show(): \Inertia\Response
    {
        return inertia('Contact');
    }
}
```

The generated route definition will look like:

```php
Route::prefix('contact')->group(function () {
    Route::get('{id}', [\App\Http\Controllers\ContactController::class, 'show'])->name('Controllers.ContactController.show');
});
```

### Smart Route Grouping

Waymaker intelligently groups routes to reduce duplication and improve readability:

- Routes with the same prefix and middleware are automatically grouped
- Prefixes and middleware are applied at the group level
- Individual routes within groups use relative URIs (no leading slash)
- Route-specific middleware is still applied to individual routes

### Smart URI Generation

Waymaker intelligently generates URIs based on your controller structure:

- Controller name becomes the base URI (e.g., `ArticleController` → `/article`)
- Route prefixes are applied to all routes in the controller
- Custom URIs in attributes are appended to the base/prefix
- Route parameters can be specified in the attribute

This automatic URI generation helps maintain consistent URL structures across your application.

### Setting route parameters and other properties

**Route attributes are required** for all controller methods that should generate routes. You can use specific HTTP method attributes to define routes. For example, you can define a route parameter like so:

```php
use HardImpact\Waymaker\Get;

...

#[Get(parameters: ['article:slug'])]
public function show(Article $article): \Inertia\Response
{
    return inertia('Article/Show', [
        'article' => $article->data->forDisplay(),
    ]);
}
```

#### Available HTTP Method Attributes

Waymaker provides specific attributes for each HTTP method:

- `#[Get]` - For GET requests
- `#[Post]` - For POST requests
- `#[Put]` - For PUT requests
- `#[Patch]` - For PATCH requests
- `#[Delete]` - For DELETE requests

Each attribute supports the following properties:
- `uri` - Custom URI path (optional, appended to base/prefix)
- `name` - Custom route name (optional)
- `parameters` - Route parameters array (optional)
- `middleware` - Route-specific middleware (optional, applied on the individual route)
- `middlewareGroup` - Named middleware **group** for the route (optional, e.g. `web`, `static`, `api`)

**Note:** Methods without a route attribute will not generate any routes.

### Middleware groups (e.g. Cloudflare / cookie-free pages)

Use a **middleware group** when routes must not sit on the default `web` stack (sessions, CSRF cookies). This is required for edge HTML caching (Cloudflare): responses with `Set-Cookie` are not cacheable.

Controller-wide:

```php
class ProjectsController extends Controller
{
    public static string $middlewareGroup = 'static';

    // Optional extra middleware applied with the group
    public static array $routeMiddleware = ['cloudflare.cache'];

    #[Get(uri: '/', name: 'projects')]
    public function index(): Response { ... }
}
```

Per route (overrides the controller default):

```php
#[Get(uri: '/pricing', name: 'pricing', middlewareGroup: 'static')]
public function pricing(): Response { ... }

#[Get(uri: '/dashboard', name: 'dashboard', middlewareGroup: 'web', middleware: 'auth')]
public function dashboard(): Response { ... }
```

Generated output looks like:

```php
Route::middleware(['static', 'cloudflare.cache'])->group(function (): void {
    Route::get('/', [...])->name('projects');
});
```

**Important:** if you register `Waymaker::routes()` inside Laravel’s `web` routes file, a `static` group is still nested under `web` (session cookies remain). Load Waymaker from `then:` so groups are top-level:

```php
// bootstrap/app.php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php', // Fortify / other non-Waymaker web routes if needed
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Waymaker::routes(); // applies middleware groups without wrapping in web
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->group('static', [
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
            \HardImpact\CloudflareCache\Http\Middleware\CacheResponse::class,
        ]);
    })
    ->create();
```

Define `static` however you like; for Cloudflare HTML caching it must **not** include `StartSession` / cookie encryption that queues `Set-Cookie`.

#### Examples

```php
use HardImpact\Waymaker\{Get, Post, Put, Delete};

class ArticleController extends Controller
{
    #[Get]
    public function index(): \Inertia\Response
    {
        // GET /articles
    }

    #[Post(middleware: 'throttle:5,1')]
    public function store(Request $request): RedirectResponse
    {
        // POST /articles with rate limiting
    }

    #[Put(parameters: ['article:slug'])]
    public function update(Request $request, Article $article): RedirectResponse
    {
        // PUT /articles/{article:slug}
    }

    #[Delete(name: 'articles.remove')]
    public function destroy(Article $article): RedirectResponse
    {
        // DELETE /articles/{id} with custom route name
    }
}
```

Other route properties are also supported like `middleware`. Besides setting middleware on specific methods you can also set them at the controller level, just as a prefix or middleware group:

```php
class ArticleController extends Controller
{
    protected static string $routePrefix = 'articles';
    protected static array $routeMiddleware = ['auth', 'verified'];
    // public static string $middlewareGroup = 'web'; // optional named group

    ...
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Feel free to contribute. Make sure to add/update tests for new or improved features.

## Troubleshooting

### Routes not being generated

1. **Missing route attribute**: Ensure all controller methods have a route attribute (`#[Get]`, `#[Post]`, etc.). Methods without attributes are ignored.
2. **Controller not in expected directory**: Waymaker scans `app/Http/Controllers` by default. Ensure your controllers are in this directory or a subdirectory.
3. **Vite not running**: The `vite-plugin-run` only triggers during `vite dev`. Run `php artisan waymaker:generate` manually for production builds.

### Duplicate route error

If you see a `RuntimeException` about duplicate routes:
- Check that you don't have multiple methods with the same HTTP method and URI
- Ensure route prefixes don't create conflicts between controllers
- Use unique route names if needed via the `name` parameter

### Routes file not included

Make sure your `routes/web.php` (or appropriate routes file) includes:

```php
use HardImpact\Waymaker\Facades\Waymaker;

Waymaker::routes();
```

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [nckrtl](https://github.com/nckrtl)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
