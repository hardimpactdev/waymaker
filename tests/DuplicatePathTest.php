<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use HardImpact\Waymaker\Waymaker;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
    $this->setupWaymaker();
});

afterEach(function () {
    $this->tearDownFixtures();
});

test('single segment controller without prefix does not duplicate path', function () {
    $controllerCode = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Post;
use Illuminate\Http\Request;

class ChangeLanguageController
{
    #[Post(middleware: ['auth', 'active'])]
    public function changeLanguage(Request $request)
    {
        return response()->json(['message' => 'Language changed']);
    }
}
PHP;

    file_put_contents($this->tempPath.'/ChangeLanguageController.php', $controllerCode);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the route definition
    $routeFound = false;
    foreach ($definitions as $definition) {
        if (str_contains($definition, 'ChangeLanguageController') && str_contains($definition, 'changeLanguage')) {
            $routeFound = true;

            // Check that the route URI is correct (should be '/change-language', not '/change-language/change-language')
            expect($definition)->toContain("Route::post('/change-language'");
            expect($definition)->not->toContain("Route::post('/change-language/change-language'");
            break;
        }
    }

    expect($routeFound)->toBeTrue();
});

test('multi segment controller without prefix does not duplicate path', function () {
    $controllerCode = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;

class UserProfileController
{
    #[Get]
    public function display()
    {
        return response()->json(['message' => 'User profile']);
    }
}
PHP;

    file_put_contents($this->tempPath.'/UserProfileController.php', $controllerCode);

    // Generate routes
    $definitions = Waymaker::generateRouteDefinitions();

    // Find the route definition
    $routeFound = false;
    foreach ($definitions as $definition) {
        if (str_contains($definition, 'UserProfileController') && str_contains($definition, 'display')) {
            $routeFound = true;

            // Check that the route URI is correct (should be '/user-profile/display', not '/user-profile/user-profile')
            expect($definition)->toContain("Route::get('/user-profile/display'");
            expect($definition)->not->toContain("Route::get('/user-profile/user-profile'");
            break;
        }
    }

    expect($routeFound)->toBeTrue();
});
