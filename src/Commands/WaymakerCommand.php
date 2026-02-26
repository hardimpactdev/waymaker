<?php

namespace HardImpact\Waymaker\Commands;

use HardImpact\Waymaker\Waymaker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WaymakerCommand extends Command
{
    public $signature = 'waymaker:generate';

    public $description = 'Generate routes for the application';

    public function handle(): int
    {
        $filePath = base_path('routes/waymaker.php');

        // Ensure the routes directory exists
        if (! File::exists(dirname($filePath))) {
            File::makeDirectory(dirname($filePath), 0755, true);
        }

        // Generate routes
        $routes = Waymaker::generateRouteDefinitions();

        // Compose the full file content with strict types, opening tag + use statement
        $content = "<?php\n\ndeclare(strict_types=1);\n\nuse Illuminate\Support\Facades\Route;\n\n".implode("\n", $routes)."\n";

        // Save the file
        File::put($filePath, $content);

        $this->info('Waymaker routes dumped successfully to routes/waymaker.php');

        return self::SUCCESS;
    }
}
