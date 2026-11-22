<?php

namespace Webkul\StatamicIntegration\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\View\FileViewFinder;
use Statamic\Facades\User as StatamicUser;
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

        $theme = $this->resolveTheme($request);

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

    protected function resolveTheme(Request $request): ?CmsThemeInstallation
    {
        if ($request->filled('cms_theme_preview')) {
            abort_unless(URL::hasValidSignature($request), 403, 'This theme preview link is invalid or expired.');

            $krayinUser = Auth::guard('user')->user();

            abort_unless($krayinUser && (bool) $krayinUser->status, 403);

            $cmsUser = StatamicUser::fromUser($krayinUser);

            abort_unless($cmsUser && $cmsUser->hasPermission('access cp'), 403);

            $theme = CmsThemeInstallation::findOrFail((int) $request->query('cms_theme_preview'));

            $request->attributes->set('cms_theme_preview', $theme);

            return $theme;
        }

        if (! DB::getSchemaBuilder()->hasTable('cms_website_settings')) {
            return null;
        }

        $settings = DB::table('cms_website_settings')->where('id', 1)->first();

        if (! $settings?->active_theme_slug || ! $settings?->active_theme_version) {
            return null;
        }

        return CmsThemeInstallation::where('slug', $settings->active_theme_slug)
            ->where('version', $settings->active_theme_version)
            ->first();
    }
}