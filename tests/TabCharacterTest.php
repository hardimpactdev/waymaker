<?php

use HardImpact\Waymaker\Tests\Traits\TestFixtures;
use Illuminate\Support\Facades\File;

uses(TestFixtures::class);

beforeEach(function () {
    $this->setUpFixtures();
    $this->setupWaymaker();
});

afterEach(function () {
    $this->tearDownFixtures();
    // Clean up the generated routes file
    if (File::exists(base_path('routes/waymaker.php'))) {
        File::delete(base_path('routes/waymaker.php'));
    }
});

/**
 * Test that no tab characters are present in generated routes.
 */
test('generated routes contain no tab characters', function () {
    // Create a test controller
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class TabTestController
{
    protected static string $routePrefix = 'test';
    protected static array $routeMiddleware = ['auth'];

    #[Get]
    public function index()
    {
        return 'index';
    }

    #[Post(uri: 'create')]
    public function create()
    {
        return 'create';
    }
}
PHP;

    file_put_contents($this->tempPath.'/TabTestController.php', $controller);

    // Run the command
    $this->artisan('waymaker:generate')->assertSuccessful();

    // Read the generated file
    $generatedContent = File::get(base_path('routes/waymaker.php'));

    // Check for tab characters
    $containsTabs = str_contains($generatedContent, "\t");
    expect($containsTabs)->toBeFalse();

    // Count actual tab characters
    $tabCount = substr_count($generatedContent, "\t");
    expect($tabCount)->toBe(0);

});
