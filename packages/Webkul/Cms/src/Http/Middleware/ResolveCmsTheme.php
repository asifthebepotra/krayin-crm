<?php

namespace Webkul\Cms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Cms\Services\CmsThemeManager;

class ResolveCmsTheme
{
    public function __construct(
        protected CmsThemeManager $themes
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $theme = $this->themes->active();

        if (! $theme || ! is_dir($theme->view_path)) {
            return $next($request);
        }

        $finder = app('view')->getFinder();
        $originalPaths = $finder->getPaths();

        $finder->setPaths(array_merge([$theme->view_path], $originalPaths));
        $finder->flush();

        try {
            return $next($request);
        } finally {
            $finder->setPaths($originalPaths);
            $finder->flush();
        }
    }
}
