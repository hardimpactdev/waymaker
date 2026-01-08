## Waymaker

Automatic route generation from controller method attributes. Define routes directly on your controller methods using PHP attributes - no manual route files needed.

### Quick Decision Guide

| User Request | Action | Why |
|--------------|--------|-----|
| "Generate routes" | `php artisan waymaker:generate` | Regenerates routes from attributes |
| "Add a GET route" | Add `#[Get]` attribute to method | Declares route via attribute |
| "Add a POST route" | Add `#[Post]` attribute to method | Declares route via attribute |
| "Add middleware" | Use `middleware:` parameter | Applied to individual route |
| "Custom route name" | Use `name:` parameter | Overrides auto-generated name |

### Installation

@verbatim
<code-snippet name="Install Waymaker" lang="bash">
composer require hardimpactdev/waymaker
</code-snippet>
@endverbatim

### Setup

1. Add Waymaker routes to your routes file:

@verbatim
<code-snippet name="Include generated routes" lang="php">
// routes/web.php
use HardImpact\Waymaker\Facades\Waymaker;

Waymaker::routes();
</code-snippet>
@endverbatim

2. Configure Vite for automatic regeneration:

@verbatim
<code-snippet name="Vite configuration for auto-generation" lang="typescript">
// vite.config.ts
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
</code-snippet>
@endverbatim

### Route Attributes

All controller methods that should generate routes **must** have an explicit route attribute:

| Attribute | HTTP Method | Example |
|-----------|-------------|---------|
| `#[Get]` | GET | `#[Get(uri: 'profile')]` |
| `#[Post]` | POST | `#[Post(uri: 'submit')]` |
| `#[Put]` | PUT | `#[Put(uri: '{id}')]` |
| `#[Patch]` | PATCH | `#[Patch(uri: '{id}')]` |
| `#[Delete]` | DELETE | `#[Delete(uri: '{id}')]` |

### Attribute Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `uri` | `?string` | Route URI (default: derived from method name) |
| `name` | `?string` | Custom route name (default: auto-generated) |
| `middleware` | `array\|string\|null` | Route-specific middleware |
| `parameters` | `?array` | Route parameter constraints |

### Example Controller

@verbatim
<code-snippet name="Controller with route attributes" lang="php">
<?php

namespace App\Http\Controllers;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;
use HardImpact\Waymaker\Delete;

class ContactController extends Controller
{
    #[Get]
    public function index(): \Inertia\Response
    {
        return inertia('Contact/Index');
    }

    #[Get(uri: '{id}')]
    public function show(int $id): \Inertia\Response
    {
        return inertia('Contact/Show', ['id' => $id]);
    }

    #[Post]
    public function store(): \Illuminate\Http\RedirectResponse
    {
        // Handle form submission
        return redirect()->back();
    }

    #[Delete(uri: '{id}', middleware: 'auth')]
    public function destroy(int $id): \Illuminate\Http\RedirectResponse
    {
        // Delete contact
        return redirect()->route('Controllers.ContactController.index');
    }
}
</code-snippet>
@endverbatim

### Generated Route Names

Routes are automatically named based on the controller path:

| Controller | Method | Generated Name |
|------------|--------|----------------|
| `App\Http\Controllers\ContactController` | `index` | `Controllers.ContactController.index` |
| `App\Http\Controllers\ContactController` | `show` | `Controllers.ContactController.show` |
| `App\Http\Controllers\Admin\UserController` | `index` | `Controllers.Admin.UserController.index` |

### Smart Route Grouping

Waymaker automatically groups routes by:
- **Prefix**: Derived from controller name (ContactController -> /contact)
- **Middleware**: Routes with same middleware are grouped together

### Manual Generation

@verbatim
<code-snippet name="Manually regenerate routes" lang="bash">
php artisan waymaker:generate
</code-snippet>
@endverbatim

### Important Notes

1. **Attributes required** - Methods without route attributes are ignored
2. **Auto-generation** - With vite-plugin-run, routes regenerate on controller changes
3. **Works with Wayfinder** - Designed to complement Laravel Wayfinder for type-safe routing
4. **Idempotent** - Safe to regenerate routes at any time
5. **No manual routes** - Let Waymaker handle all route definitions
