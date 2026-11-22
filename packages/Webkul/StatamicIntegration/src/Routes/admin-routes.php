<?php

use Illuminate\Support\Facades\Route;
use Webkul\StatamicIntegration\Http\Controllers\CmsController;
use Webkul\StatamicIntegration\Http\Controllers\CmsThemeController;
use Webkul\StatamicIntegration\Http\Controllers\CmsSubmissionController;
use Webkul\StatamicIntegration\Http\Controllers\CmsWebsiteSettingsController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::get('cms', [CmsController::class, 'index'])
            ->name('cms.index');

        Route::get('cms/open', [CmsController::class, 'open'])
            ->name('cms.open');

        Route::get('cms/submissions', [CmsSubmissionController::class, 'index'])
            ->name('cms.submissions.index');

        Route::get('cms/settings', [CmsWebsiteSettingsController::class, 'index'])
            ->name('cms.settings.index');

        Route::put('cms/settings', [CmsWebsiteSettingsController::class, 'update'])
            ->name('cms.settings.update');

        Route::get('cms/themes', [CmsThemeController::class, 'index'])
            ->name('cms.themes.index');

        Route::post('cms/themes', [CmsThemeController::class, 'store'])
            ->name('cms.themes.store');

        Route::post('cms/themes/deactivate', [CmsThemeController::class, 'deactivate'])
            ->name('cms.themes.deactivate');

        Route::get('cms/themes/{theme}/preview', [CmsThemeController::class, 'preview'])
            ->whereNumber('theme')
            ->name('cms.themes.preview');

        Route::post('cms/themes/{theme}/activate', [CmsThemeController::class, 'activate'])
            ->whereNumber('theme')
            ->name('cms.themes.activate');

        Route::delete('cms/themes/{theme}', [CmsThemeController::class, 'destroy'])
            ->whereNumber('theme')
            ->name('cms.themes.destroy');
    });