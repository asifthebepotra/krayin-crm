<?php

namespace Webkul\StatamicIntegration\Tags;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Statamic\Tags\Tags;
use Webkul\StatamicIntegration\Services\CmsThemeManager;

class CmsThemeTags extends Tags
{
    public static function handle(): string
    {
        return 'cms_theme';
    }

    /**
     * Antlers usage:
     * {{ cms_theme:asset path="css/site.css" }}
     */
    public function asset(): string
    {
        $path = trim((string) $this->params->get('path', ''));

        if (
            $path === ''
            || str_contains($path, chr(92))
            || str_starts_with($path, '/')
            || in_array('..', explode('/', $path), true)
            || in_array('.', explode('/', $path), true)
        ) {
            throw new RuntimeException('Invalid CMS theme asset path.');
        }

        $theme = app(CmsThemeManager::class)->activeTheme();

        if (! $theme) {
            return '';
        }

        $relative = "themes/{$theme->slug}/{$theme->version}/assets/{$path}";
        $publicPath = public_path($relative);
        $realRoot = realpath(public_path("themes/{$theme->slug}/{$theme->version}/assets"));
        $realFile = realpath($publicPath);

        if (! $realRoot || ! $realFile || ! str_starts_with($realFile, $realRoot.DIRECTORY_SEPARATOR) || ! File::isFile($realFile)) {
            return '';
        }

        return asset($relative);
    }
}