# Plan: Invokable Controller Support for Waymaker

## Overview
Add support for single-action invokable controllers that use PHP's `__invoke` magic method. These controllers have only one action and are perfect for simple pages or single-purpose endpoints.

## Current Problem

At `src/Waymaker.php:193`, ALL magic methods are skipped:
```php
if ($method->isConstructor() || $method->isDestructor() || strpos($method->name, '__') === 0) {
    return;
}
```

This prevents `__invoke` from ever being processed, even with route attributes.

## Detailed Implementation Plan

### 1. Allow `__invoke` Method Processing

**File:** `src/Waymaker.php:193`

**Change:**
```php
// Before:
if ($method->isConstructor() || $method->isDestructor() || strpos($method->name, '__') === 0) {
    return;
}

// After:
if ($method->isConstructor() || $method->isDestructor() || 
    (strpos($method->name, '__') === 0 && $method->name !== '__invoke')) {
    return;
}
```

### 2. Special Route Naming for Invokable Controllers

**Current Behavior:**
- Regular controller: `HomeController::show()` → route name: `HomeController.show`

**Desired Behavior for Invokable:**
- Invokable controller: `ContactController::__invoke()` → route name: `ContactController`
- The controller filename/classname IS the route name (no method suffix)

**File:** `src/Waymaker.php:387-402` (`generateRouteName` method)

**Implementation:**
```php
private static function generateRouteName(string $methodName, ?string $customName, string $controllerClass): string
{
    if ($customName) {
        return $customName;
    }

    $baseNamespace = self::$controllerNamespace ?? 'App\Http\Controllers';
    $relativeClass = str_replace($baseNamespace.'\', '', $controllerClass);
    $namespacePath = str_replace('\', '.', $relativeClass);

    // For invokable controllers, don't append method name
    if ($methodName === '__invoke') {
        return $namespacePath;
    }

    return sprintf('%s.%s', $namespacePath, $methodName);
}
```

### 3. URI Generation for Invokable Controllers

**Rules:**
1. **With custom URI:** Use the custom URI as the full path
2. **With prefix:** Use prefix as the full URI (invokable = prefix is the complete route)
3. **No prefix, no custom URI:** Use `/` (root path)
4. **Controller name is NEVER used** as base URI for invokable controllers

**File:** `src/Waymaker.php:316-377` (`generateUri` method)

**Implementation:**
```php
private static function generateUri(
    ?string $prefix,
    ?string $customUri,
    ?array $parameters,
    string $controllerName,
    string $methodName
): string {
    // Special handling for invokable controllers
    if ($methodName === '__invoke') {
        // If custom URI provided, use it as the full path
        if ($customUri !== null) {
            return $customUri === '/' ? '/' : '/' . ltrim($customUri, '/');
        }
        
        // If prefix is set, that's the complete route
        if ($prefix) {
            return '/' . trim($prefix, '/');
        }
        
        // Default to root
        return '/';
    }
    
    // ... rest of existing logic for non-invokable methods
}
```

### 4. Controller Detection Helper

**File:** `src/Waymaker.php` (new method)

Add a helper to detect if a controller is invokable (has only `__invoke` as a public method with route attribute):
```php
private static function isInvokableController(ReflectionClass $reflection): bool
{
    $publicMethods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
    $routeMethods = [];
    
    foreach ($publicMethods as $method) {
        if ($method->class !== $reflection->getName()) {
            continue;
        }
        
        foreach ($method->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof RouteAttribute) {
                $routeMethods[] = $method->name;
                break;
            }
        }
    }
    
    // It's invokable if __invoke is the ONLY route method
    return count($routeMethods) === 1 && $routeMethods[0] === '__invoke';
}
```

## Usage Examples

### Example 1: Root Route Invokable
```php
<?php
namespace App\Http\Controllers;

use HardImpact\Waymaker\Get;

class HomeController extends Controller
{
    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Home');
    }
}
```

**Generated Route:**
```php
Route::get('/', [\App\Http\Controllers\HomeController::class, '__invoke'])->name('HomeController');
```

### Example 2: Prefixed Invokable
```php
<?php
namespace App\Http\Controllers;

use HardImpact\Waymaker\Get;

class ContactController extends Controller
{
    protected static string $routePrefix = 'contact';
    protected static string $routeMiddleware = 'guest';

    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Contact');
    }
}
```

**Generated Route:**
```php
Route::prefix('contact')->middleware('guest')->group(function () {
    Route::get('', [\App\Http\Controllers\ContactController::class, '__invoke'])->name('ContactController');
});
```

### Example 3: Custom URI Invokable
```php
<?php
namespace App\Http\Controllers;

use HardImpact\Waymaker\Get;

class AboutController extends Controller
{
    #[Get(uri: 'about-us')]
    public function __invoke(): \Inertia\Response
    {
        return inertia('About');
    }
}
```

**Generated Route:**
```php
Route::get('about-us', [\App\Http\Controllers\AboutController::class, '__invoke'])->name('AboutController');
```

### Example 4: Namespaced Invokable
```php
<?php
namespace App\Http\Controllers\Admin\Settings;

use HardImpact\Waymaker\Get;

class ProfileController extends Controller
{
    protected static string $routePrefix = 'profile';
    protected static string $routeMiddleware = 'auth';

    #[Get]
    public function __invoke(): \Inertia\Response
    {
        return inertia('Admin/Settings/Profile');
    }
}
```

**Generated Route:**
```php
Route::prefix('profile')->middleware('auth')->group(function () {
    Route::get('', [\App\Http\Controllers\Admin\Settings\ProfileController::class, '__invoke'])->name('Admin.Settings.ProfileController');
});
```

## Test Cases Required

### Test 1: Basic Invokable (No Prefix, No Custom URI)
```php
test('generates root route for invokable controller without prefix', function () {
    // HomeController with __invoke and #[Get], no prefix
    // Expected: Route::get('/', [...])->name('HomeController')
});
```

### Test 2: Invokable With Prefix
```php
test('generates prefixed route for invokable controller', function () {
    // ContactController with __invoke, #[Get], $routePrefix = 'contact'
    // Expected: Route::prefix('contact')->group with Route::get('', ...)
});
```

### Test 3: Invokable With Custom URI
```php
test('generates custom URI route for invokable controller', function () {
    // AboutController with __invoke, #[Get(uri: 'about-us')]
    // Expected: Route::get('about-us', ...)
});
```

### Test 4: Invokable With Middleware
```php
test('applies middleware to invokable controller routes', function () {
    // Controller with __invoke, #[Get(middleware: 'auth')]
    // Expected: Route with middleware
});
```

### Test 5: Invokable Route Name (No __invoke Suffix)
```php
test('invokable controller route name excludes __invoke', function () {
    // Any invokable controller
    // Expected: name('ControllerName') NOT name('ControllerName.__invoke')
});
```

### Test 6: Invokable With Parameters
```php
test('supports parameters on invokable controllers', function () {
    // ShowController with __invoke, #[Get(parameters: ['id'])]
    // Expected: Route::get('{id}', ...)
});
```

## Files to Modify

1. **src/Waymaker.php**
   - Line ~193: Update magic method skip condition
   - Line ~316: Add invokable logic to `generateUri()`
   - Line ~387: Add invokable logic to `generateRouteName()`

2. **tests/** (new test file: `tests/InvokableControllerTest.php`)
   - Add all test cases listed above
   - Add fixture controllers for testing

3. **README.md**
   - Add section: "Invokable Controllers"
   - Include all 4 usage examples

4. **AGENTS.md** (update patterns section)
   - Add invokable controller pattern example

## Edge Cases to Handle

1. **Mixed controllers** - Controllers with both `__invoke` AND other methods should treat `__invoke` as a regular method (not special invokable logic)
2. **Custom name attribute** - `#[Get(name: 'custom')]` should override the default controller-based name
3. **Empty prefix with invokable** - Should default to `/`
4. **Custom URI '/' with invokable** - Should produce root route `/`
5. **Subdirectory invokables** - Should include full namespace path in route name: `Admin.Settings.ProfileController`

## Implementation Order

1. Modify `Waymaker.php` - Allow `__invoke` processing
2. Modify `generateRouteName()` - Handle invokable naming
3. Modify `generateUri()` - Handle invokable URI logic
4. Create test file with fixtures
5. Run full test suite
6. Update documentation

## Expected Output Examples

```php
// Input: HomeController (invokable, no prefix)
Route::get('/', [\App\Http\Controllers\HomeController::class, '__invoke'])->name('HomeController');

// Input: ContactController (invokable, prefix='contact')
Route::prefix('contact')->group(function () {
    Route::get('', [\App\Http\Controllers\ContactController::class, '__invoke'])->name('ContactController');
});

// Input: AboutController (invokable, custom URI='about-us')
Route::get('about-us', [\App\Http\Controllers\AboutController::class, '__invoke'])->name('AboutController');

// Input: Admin/DashboardController (invokable, in subdirectory)
Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, '__invoke'])->name('Admin.DashboardController');
```

## Migration Notes for Users

This is a **new feature**, not a breaking change. Existing controllers with multiple methods continue to work as before. Users can now optionally use `__invoke` for single-action controllers.

## Success Criteria

- [ ] `__invoke` method is processed when it has a route attribute
- [ ] Route name uses controller name only (no `__invoke` suffix)
- [ ] URI generation follows invokable rules (no controller name in URI)
- [ ] All 6 test cases pass
- [ ] Full test suite still passes
- [ ] Documentation updated with examples
