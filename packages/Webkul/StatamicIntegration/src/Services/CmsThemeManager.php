<?php

namespace Webkul\StatamicIntegration\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Webkul\StatamicIntegration\Models\CmsThemeInstallation;

class CmsThemeManager
{
    public function activeTheme(): ?CmsThemeInstallation
    {
        $settings = DB::table('cms_website_settings')->where('id', 1)->first();

        if (! $settings?->active_theme_slug || ! $settings?->active_theme_version) {
            return null;
        }

        return CmsThemeInstallation::where('slug', $settings->active_theme_slug)
            ->where('version', $settings->active_theme_version)
            ->first();
    }

    public function activate(CmsThemeInstallation $theme): void
    {
        $viewsPath = storage_path('app/'.$theme->install_path.'/views');

        if (! File::isDirectory($viewsPath)) {
            throw new RuntimeException('The selected theme does not contain an installed views directory.');
        }

        $settingsExists = DB::table('cms_website_settings')->where('id', 1)->exists();

        $values = [
            'active_theme_slug' => $theme->slug,
            'active_theme_version' => $theme->version,
            'updated_at' => now(),
        ];

        if (! $settingsExists) {
            $values['created_at'] = now();
        }

        DB::table('cms_website_settings')->updateOrInsert(['id' => 1], $values);
    }

    public function uninstall(CmsThemeInstallation $theme): void
    {
        $active = $this->activeTheme();

        if (
            $active
            && $active->slug === $theme->slug
            && $active->version === $theme->version
        ) {
            throw new RuntimeException('Deactivate this theme before removing it.');
        }

        $themeRoot = realpath(storage_path('app/statamic-themes'));
        $installPath = realpath(storage_path('app/'.$theme->install_path));

        if (
            ! $themeRoot
            || ! $installPath
            || ! str_starts_with($installPath, $themeRoot.DIRECTORY_SEPARATOR)
        ) {
            throw new RuntimeException('The installed theme path is invalid. The theme was not removed.');
        }

        $publicRoot = realpath(public_path('themes'));
        $publicTheme = realpath(public_path("themes/{$theme->slug}/{$theme->version}"));

        if ($publicTheme && (! $publicRoot || ! str_starts_with($publicTheme, $publicRoot.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('The public theme asset path is invalid. The theme was not removed.');
        }

        File::deleteDirectory($installPath);

        if ($publicTheme) {
            File::deleteDirectory($publicTheme);
        }

        $theme->delete();
    }
}