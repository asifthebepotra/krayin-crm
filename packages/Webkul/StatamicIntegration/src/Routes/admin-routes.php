<?php

use Illuminate\Support\Facades\Route;
use Webkul\StatamicIntegration\Http\Controllers\CmsController;
use Webkul\StatamicIntegration\Http\Controllers\CmsThemeController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::get('cms', [CmsController::class, 'index'])
            ->name('cms.index');

        Route::get('cms/open', [CmsController::class, 'open'])
            ->name('cms.open');

        Route::get('cms/themes', [CmsThemeController::class, 'index'])
            ->name('cms.themes.index');

        Route::post('cms/themes', [CmsThemeController::class, 'store'])
            ->name('cms.themes.store');

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