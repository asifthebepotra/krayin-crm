<?php

namespace Webkul\StatamicIntegration\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use RuntimeException;
use Webkul\StatamicIntegration\Models\CmsThemeInstallation;
use Webkul\StatamicIntegration\Services\CmsThemeManager;
use Webkul\StatamicIntegration\Services\ThemeInstaller;

class CmsThemeController extends Controller
{
    public function index(CmsThemeManager $manager)
    {
        return view('statamic_integration::themes.index', [
            'themes' => Schema::hasTable('cms_theme_installations')
                ? CmsThemeInstallation::orderBy('name')->orderByDesc('installed_at')->get()
                : collect(),
            'activeTheme' => $manager->activeTheme(),
            'tablesReady' => Schema::hasTable('cms_theme_installations') && Schema::hasTable('cms_website_settings'),
        ]);
    }

    public function store(Request $request, ThemeInstaller $installer)
    {
        $request->validate([
            'theme_archive' => ['required', 'file', 'mimes:zip', 'max:15360'],
        ]);

        try {
            $theme = $installer->install($request->file('theme_archive'));
        } catch (RuntimeException $exception) {
            return back()
                ->withInput()
                ->withErrors(['theme_archive' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', "Theme [{$theme->name}] {$theme->version} was installed.");
    }

    public function preview(int $theme)
    {
        $theme = CmsThemeInstallation::findOrFail($theme);

        $url = URL::temporarySignedRoute(
            'statamic.site.root',
            now()->addMinutes(10),
            ['cms_theme_preview' => $theme->id]
        );

        return redirect()->away($url);
    }

    public function activate(int $theme, CmsThemeManager $manager)
    {
        $theme = CmsThemeInstallation::findOrFail($theme);

        try {
            $manager->activate($theme);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['theme' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', "Theme [{$theme->name}] is now active.");
    }

    public function destroy(int $theme, CmsThemeManager $manager)
    {
        $theme = CmsThemeInstallation::findOrFail($theme);

        try {
            $manager->uninstall($theme);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['theme' => $exception->getMessage()]);
        }

        return redirect()
            ->route('admin.cms.themes.index')
            ->with('success', "Theme [{$theme->name}] was removed.");
    }
}