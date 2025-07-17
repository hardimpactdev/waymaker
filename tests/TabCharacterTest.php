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

    // Display any lines that might contain tabs
    $lines = explode("\n", $generatedContent);
    foreach ($lines as $lineNum => $line) {
        if (str_contains($line, "\t")) {
            $visibleLine = str_replace("\t", '<TAB>', $line);
            echo "Line {$lineNum}: {$visibleLine}\n";
        }
    }

    // Also check the raw bytes of the first route line
    $routeLines = array_filter($lines, fn ($line) => str_contains($line, 'Route::get') || str_contains($line, 'Route::post'));
    if (! empty($routeLines)) {
        $firstRouteLine = reset($routeLines);
        echo "\nFirst route line analysis:\n";
        echo 'Raw: '.json_encode($firstRouteLine)."\n";

        // Check first 8 characters
        $first8 = substr($firstRouteLine, 0, 8);
        echo 'First 8 chars: '.json_encode($first8)."\n";
        echo 'First 8 bytes: ';
        for ($i = 0; $i < strlen($first8); $i++) {
            echo ord($first8[$i]).' ';
        }
        echo "\n";

        // Space is ASCII 32, Tab is ASCII 9
        for ($i = 0; $i < strlen($first8); $i++) {
            $char = $first8[$i];
            if (ord($char) === 9) {
                echo "Found TAB at position {$i}\n";
            }
        }
    }
});
