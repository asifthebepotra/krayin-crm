<?php

namespace Webkul\StatamicIntegration\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Statamic;

class InstallStarterContent extends Command
{
    protected $signature = 'statamic-integration:install-starter-content';

    protected $description = 'Install the starter Pages and Blog structure for the Statamic website';

    public function handle(): int
    {
        if (! class_exists(Statamic::class)) {
            $this->components->error('Statamic CMS is not installed.');

            return self::FAILURE;
        }

        $source = __DIR__.'/../../Resources/Statamic';

        if (! File::isDirectory($source)) {
            $this->components->error('The Statamic starter content directory is missing.');

            return self::FAILURE;
        }

        $copied = 0;
        $skipped = 0;

        foreach (File::allFiles($source) as $file) {
            $relativePath = $file->getRelativePathname();
            $destination = base_path($relativePath);

            if (File::exists($destination)) {
                $skipped++;

                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            if (! File::copy($file->getPathname(), $destination)) {
                $this->components->error("Unable to copy [{$relativePath}].");

                return self::FAILURE;
            }

            $copied++;
        }

        $this->components->info("Installed {$copied} starter files.");

        if ($skipped > 0) {
            $this->components->warn("Skipped {$skipped} existing files. Your current content was preserved.");
        }

        $this->components->info('Open the Statamic Control Panel and review the Pages and Blog collections.');

        return self::SUCCESS;
    }
}