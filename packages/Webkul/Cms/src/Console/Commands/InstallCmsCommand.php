<?php

namespace Webkul\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Statamic\Facades\YAML;
use Webkul\Cms\Services\CmsSettingsManager;

class InstallCmsCommand extends Command
{
    protected $signature = 'cms:install';

    protected $description = 'Install Krayin CMS starter content and Statamic roles';

    public function handle(CmsSettingsManager $settingsManager): int
    {
        if (! class_exists(\Statamic\Statamic::class)) {
            $this->components->error('Statamic CMS is not installed. Install the pinned Statamic dependency first.');

            return self::FAILURE;
        }

        $this->installDefaultFiles();

        $this->installDefaultRoles();

        $settingsManager->current();

        $this->components->info('Krayin CMS starter content is ready.');
        $this->components->info('Krayin manages employee accounts. Assign CMS roles from the CMS Access screen.');

        return self::SUCCESS;
    }

    /**
     * Copy starter files only when the destination does not already exist.
     *
     * Existing website content, blueprints and templates are never overwritten.
     */
    protected function installDefaultFiles(): void
    {
        $packageRoot = dirname(__DIR__, 3).'/Resources/statamic';

        $files = [
            'content/collections/pages.yaml' => base_path('content/collections/pages.yaml'),
            'content/collections/blog.yaml' => base_path('content/collections/blog.yaml'),
            'content/collections/pages/home.md' => base_path('content/collections/pages/home.md'),
            'content/collections/pages/blog.md' => base_path('content/collections/pages/blog.md'),
            'content/collections/pages/contact.md' => base_path('content/collections/pages/contact.md'),
            'content/trees/collections/pages.yaml' => base_path('content/trees/collections/pages.yaml'),
            'content/assets/assets.yaml' => base_path('content/assets/assets.yaml'),
            'content/navigation/main.yaml' => base_path('content/navigation/main.yaml'),
            'content/trees/navigation/main.yaml' => base_path('content/trees/navigation/main.yaml'),
            'content/forms/contact.yaml' => base_path('content/forms/contact.yaml'),
            'resources/blueprints/collections/pages/page.yaml' => resource_path('blueprints/collections/pages/page.yaml'),
            'resources/blueprints/collections/blog/blog_post.yaml' => resource_path('blueprints/collections/blog/blog_post.yaml'),
            'resources/blueprints/forms/contact.yaml' => resource_path('blueprints/forms/contact.yaml'),
            'resources/views/layout.antlers.html' => resource_path('views/layout.antlers.html'),
            'resources/views/default.antlers.html' => resource_path('views/default.antlers.html'),
            'resources/views/contact.antlers.html' => resource_path('views/contact.antlers.html'),
            'resources/views/blog/index.antlers.html' => resource_path('views/blog/index.antlers.html'),
            'resources/views/blog/show.antlers.html' => resource_path('views/blog/show.antlers.html'),
        ];

        $created = [];

        foreach ($files as $source => $destination) {
            $sourcePath = $packageRoot.'/'.$source;

            if (! File::exists($sourcePath)) {
                throw new \RuntimeException("CMS starter file is missing: {$sourcePath}");
            }

            if (File::exists($destination)) {
                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            File::copy($sourcePath, $destination);

            $created[] = $destination;
        }

        if (count($created)) {
            $this->components->info('Installed '.count($created).' CMS starter files.');
        } else {
            $this->components->info('CMS starter files already exist. Existing files were preserved.');
        }
    }

    /**
     * Merge the bundled CMS roles without replacing customized role files.
     */
    protected function installDefaultRoles(): void
    {
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
    }
}
