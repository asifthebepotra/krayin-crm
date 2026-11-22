<?php

namespace Webkul\StatamicIntegration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\View\FileViewFinder;
use Symfony\Component\HttpFoundation\Response;
use Webkul\StatamicIntegration\Models\CmsThemeInstallation;

class ApplyActiveStatamicTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        if (! in_array($routeName, ['statamic.site', 'statamic.site.root'], true)) {
            return $next($request);
        }

        if (! DB::getSchemaBuilder()->hasTable('cms_website_settings')) {
            return $next($request);
        }

        $settings = DB::table('cms_website_settings')->where('id', 1)->first();

        if (! $settings?->active_theme_slug || ! $settings?->active_theme_version) {
            return $next($request);
        }

        $theme = CmsThemeInstallation::where('slug', $settings->active_theme_slug)
            ->where('version', $settings->active_theme_version)
            ->first();

        if (! $theme) {
            return $next($request);
        }

        $viewsPath = storage_path('app/'.$theme->install_path.'/views');

        if (! File::isDirectory($viewsPath)) {
            return $next($request);
        }

        $finder = app('view')->getFinder();

        if (! $finder instanceof FileViewFinder) {
            return $next($request);
        }

        $originalPaths = $finder->getPaths();

        $finder->prependLocation($viewsPath);
        $finder->flush();

        try {
            return $next($request);
        } finally {
            $finder->setPaths($originalPaths);
            $finder->flush();
        }
    }
}