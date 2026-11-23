<?php

namespace Webkul\Cms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use RuntimeException;
use Webkul\Cms\Services\CmsThemeManager;

class CmsThemeController extends Controller
{
    public function __construct(
        protected CmsThemeManager $themes
    ) {}

    public function index(): View
    {
        $ready = Schema::hasTable('cms_themes');

        return view('cms::themes', [
            'ready' => $ready,
            'themes' => $ready ? $this->themes->all() : collect(),
            'active' => $ready ? $this->themes->active() : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'theme_zip' => ['required', 'file', 'mimes:zip', 'max:25600'],
        ]);

        $theme = $this->themes->install($validated['theme_zip']);

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', trans('cms::app.themes.installed', [
                'name' => $theme->name,
                'version' => $theme->version,
            ]));
    }

    public function activate(string $slug, string $version): RedirectResponse
    {
        $theme = $this->themes->activate($slug, $version);

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', trans('cms::app.themes.activated', [
                'name' => $theme->name,
                'version' => $theme->version,
            ]));
    }

    public function destroy(string $slug, string $version): RedirectResponse
    {
        try {
            $this->themes->remove($slug, $version);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['theme' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', trans('cms::app.themes.removed'));
    }
}
