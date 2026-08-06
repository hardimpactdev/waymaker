<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;
use Illuminate\Support\Facades\File;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
});

afterEach(function () {
    $this->tearDownFixtures();
});

test('it generates routes with a controller middleware group', function () {
    $content = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class StaticPageController
{
    public static string $middlewareGroup = 'static';

    #[Get(uri: '/', name: 'home')]
    public function index()
    {
        return 'home';
    }

    #[Get(uri: '/about', name: 'about')]
    public function about()
    {
        return 'about';
    }
}
PHP;

    File::put($this->tempPath.'/StaticPageController.php', $content);

    Waymaker::setControllerPath($this->tempPath, 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp');
    $definitions = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $definitions);

    expect($routesString)->toContain("Route::middleware('static')->group(function (): void {");
    expect($routesString)->toContain("->name('home')");
    expect($routesString)->toContain("->name('about')");
});

test('it allows per-route middleware group override', function () {
    $content = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class MixedGroupController
{
    public static string $middlewareGroup = 'web';

    #[Get(uri: '/dashboard', name: 'dashboard')]
    public function dashboard()
    {
        return 'dashboard';
    }

    #[Get(uri: '/pricing', name: 'pricing', middlewareGroup: 'static')]
    public function pricing()
    {
        return 'pricing';
    }
}
PHP;

    File::put($this->tempPath.'/MixedGroupController.php', $content);

    Waymaker::setControllerPath($this->tempPath, 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp');
    $definitions = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $definitions);

    expect($routesString)->toContain("Route::middleware('web')->group(function (): void {");
    expect($routesString)->toContain("Route::middleware('static')->group(function (): void {");
    expect($routesString)->toContain("->name('dashboard')");
    expect($routesString)->toContain("->name('pricing')");
});

test('it combines middleware group with controller and route middleware', function () {
    $content = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class CachedPageController
{
    public static string $middlewareGroup = 'static';

    public static array $routeMiddleware = ['cloudflare.cache'];

    #[Get(uri: '/projects', name: 'projects', middleware: 'throttle:60,1')]
    public function index()
    {
        return 'projects';
    }
}
PHP;

    File::put($this->tempPath.'/CachedPageController.php', $content);

    Waymaker::setControllerPath($this->tempPath, 'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp');
    $definitions = Waymaker::generateRouteDefinitions();
    $routesString = implode("\n", $definitions);

    expect($routesString)->toContain("Route::middleware(['static', 'cloudflare.cache'])->group(function (): void {");
    expect($routesString)->toContain("->middleware('throttle:60,1')");
    expect($routesString)->toContain("->name('projects')");
});
