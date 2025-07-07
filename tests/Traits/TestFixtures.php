<?php

namespace HardImpact\Waymaker\Tests\Traits;

use Illuminate\Support\Facades\File;
use HardImpact\Waymaker\Waymaker;
use Symfony\Component\Finder\Finder;

trait TestFixtures
{
    protected string $tempPath;

    /**
     * Set up the temporary path for controller fixtures.
     */
    protected function setUpFixtures(): void
    {
        $this->tempPath = __DIR__.'/../../tests/Http/Controllers/temp';

        if (! File::isDirectory($this->tempPath)) {
            File::makeDirectory($this->tempPath, 0777, true);
        }
    }

    /**
     * Clean up the temporary path.
     */
    protected function tearDownFixtures(): void
    {
        if (File::isDirectory($this->tempPath)) {
            File::deleteDirectory($this->tempPath);
        }
    }

    /**
     * Copy a fixture controller from the Fixtures directory to the temp directory.
     */
    protected function copyFixtureController(string $source, ?string $destination = null): string
    {
        $sourcePath = __DIR__.'/../Fixtures/Controllers/'.$source;
        $destPath = $this->tempPath.'/'.($destination ?? basename($source));

        if (File::exists($sourcePath)) {
            File::copy($sourcePath, $destPath);
        } else {
            throw new \InvalidArgumentException("Fixture not found: {$sourcePath}");
        }

        return $destPath;
    }

    /**
     * Copy all controllers from a subdirectory in Fixtures to the temp directory.
     */
    protected function copyFixtureControllers(string $subDirectory): void
    {
        $sourcePath = __DIR__.'/../Fixtures/Controllers/'.$subDirectory;

        if (! File::isDirectory($sourcePath)) {
            throw new \InvalidArgumentException("Fixture directory not found: {$sourcePath}");
        }

        foreach (File::files($sourcePath) as $file) {
            File::copy($file->getPathname(), $this->tempPath.'/'.basename($file));
        }
    }

    /**
     * Set up the Waymaker to use the temp path.
     */
    protected function setupWaymaker(): void
    {
        Waymaker::setControllerPath(
            $this->tempPath,
            'HardImpact\\Waymaker\\Tests\\Http\\Controllers\\temp'
        );
    }

    /**
     * Debug helper to list all controllers in the temp directory.
     */
    protected function debugControllers(): array
    {
        $result = [];

        if (File::isDirectory($this->tempPath)) {
            $finder = new Finder;
            $files = $finder->files()->in($this->tempPath)->name('*Controller.php');

            foreach ($files as $file) {
                $result[] = [
                    'filename' => $file->getFilename(),
                    'realpath' => $file->getRealPath(),
                    'size' => $file->getSize(),
                    'content' => File::get($file->getPathname()),
                ];
            }
        }

        return $result;
    }
}
