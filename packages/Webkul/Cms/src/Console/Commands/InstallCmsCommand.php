<?php

namespace Webkul\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;

class InstallCmsCommand extends Command
{
    protected $signature = 'cms:install';

    protected $description = 'Prepare Krayin CMS roles for Statamic';

    public function handle(): int
    {
        if (! class_exists(\Statamic\Statamic::class)) {
            $this->components->error('Statamic CMS is not installed. Install the pinned Statamic dependency first.');

            return self::FAILURE;
        }

        $path = resource_path('users/roles.yaml');

        File::ensureDirectoryExists(dirname($path));

        $defaults = YAML::parse(
            File::get(__DIR__.'/../../Resources/users/roles.yaml')
        ) ?? [];

        $roles = File::exists($path)
            ? (YAML::parse(File::get($path)) ?? [])
            : [];

        $added = [];

        foreach ($defaults as $handle => $definition) {
            if (! array_key_exists($handle, $roles)) {
                $roles[$handle] = $definition;
                $added[] = $handle;
            }
        }

        File::put($path, YAML::dump($roles));

        if (count($added)) {
            $this->components->info('Added CMS roles: '.implode(', ', $added));
        } else {
            $this->components->info('Default CMS roles already exist. Existing role definitions were preserved.');
        }

        $this->components->info('Krayin manages employee accounts. Use the CMS Access screen to assign CMS roles.');

        return self::SUCCESS;
    }
}
