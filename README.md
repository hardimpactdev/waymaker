# Waymaker

This Laravel packages lets you generate a routes file based on your public controller methods. This package works particularly well with Laravel Wayfinder, as it allows you to reference controller methods instead of just routes. Based on the method signature in your controllers we could generate a routes file, automating route management entirely.

## Installation

You can install the package via composer:

```bash
composer require hardimpactdev/waymaker
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
                run: ["php", "artisan", "waymaker:make"],
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

Now you're all set. Running vite dev should nog generate the routes based on your controller methods. On file change of any controller the routes file will be regenerated.

### Route definition structure

The way routes are generated are pretty opionated. The naming convention of routes is inspired by how Laravel Wayfinder exposes routes/actions. For this controller:

```php
<?php

namespace App\Http\Controllers;

class ContactController extends Controller
{
    public function show(): \Inertia\Response
    {
        return inertia('Contact');
    }
}
```

The generated route definition will look like:

```php
Route::get('/contact/{id}', [\App\Http\Controllers\ContactController::class, 'show'])->name('Controllers.ContactController.show');
```

### Smart URI Generation

Waymaker intelligently generates URIs based on RESTful controller method conventions:

- `index()`, `create()`, `store()`: `/resource`
- `show()`, `edit()`, `update()`, `destroy()`: `/resource/{id}`
- Other custom methods: `/resource/method-name`

This automatic URI generation prevents route conflicts when a controller has multiple methods with the same HTTP verb.

### Setting route parameters and other properties.

To influence the route that is being generated you can use specific HTTP method attributes. For example, you can define a route parameter like so:

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
- `uri` - Custom URI path (optional)
- `name` - Custom route name (optional)
- `parameters` - Route parameters array (optional)
- `middleware` - Route-specific middleware (optional)

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

Other route properties are also supported like `middleware`. Besides setting middleware on specific methods you can also set them at the controller level, just as a prefix:

```php
class ArticleController extends Controller
{
    protected static string $routePrefix = 'articles';
    protected static string $routeMiddleware = 'auth:verified';

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

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [nckrtl](https://github.com/nckrtl)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
