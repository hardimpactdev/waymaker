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
 * Test that the command generates routes with correct indentation.
 */
test('waymaker command generates routes file with correct indentation', function () {
    // Create a test controller
    $controller = <<<'PHP'
<?php

namespace HardImpact\Waymaker\Tests\Http\Controllers\temp;

use HardImpact\Waymaker\Get;
use HardImpact\Waymaker\Post;

class CommandTestController
{
    protected static string $routePrefix = 'test';
    protected static array $routeMiddleware = ['auth'];

    #[Get]
    public function index()
    {
        return 'index';
    }

    #[Post]
    public function store()
    {
        return 'store';
    }
}
PHP;

    file_put_contents($this->tempPath.'/CommandTestController.php', $controller);

    // Run the command
    $this->artisan('waymaker:generate')
        ->assertSuccessful()
        ->expectsOutput('Waymaker routes dumped successfully to routes/waymaker.php');

    // Read the generated file
    $generatedContent = File::get(base_path('routes/waymaker.php'));

    // Display the content with visible whitespace
    echo "\n=== Generated File Content (with visible spaces) ===\n";
    $lines = explode("\n", $generatedContent);
    foreach ($lines as $line) {
        $visibleLine = str_replace(' ', '·', $line);
        echo $visibleLine."\n";
    }
    echo "=== End Content ===\n";

    // Check for correct indentation
    $routeLines = array_filter($lines, fn ($line) => str_contains($line, 'Route::get') || str_contains($line, 'Route::post'));

    foreach ($routeLines as $line) {
        // Check that indentation is exactly 4 spaces
        preg_match('/^(\s*)/', $line, $matches);
        $indent = $matches[1];

        expect(strlen($indent))->toBe(4);
        expect($indent)->toBe('    ');
    }

    // Also check that there are no tabs in the file
    expect($generatedContent)->not->toContain("\t");
});
