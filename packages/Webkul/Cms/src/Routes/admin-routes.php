<?php

use Illuminate\Support\Facades\Route;
use Webkul\Cms\Http\Controllers\CmsAccessController;
use Webkul\Cms\Http\Controllers\CmsController;
use Webkul\Cms\Http\Controllers\CmsSettingsController;
use Webkul\Cms\Http\Controllers\CmsSubmissionController;
use Webkul\Cms\Http\Controllers\CmsThemeController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(trim(config('app.admin_path', 'admin'), '/'))
    ->name('admin.')
    ->group(function () {
        Route::get('cms', [CmsController::class, 'index'])
            ->name('cms.index');

        Route::get('cms/settings', [CmsSettingsController::class, 'edit'])
            ->name('cms.settings.edit');

        Route::put('cms/settings', [CmsSettingsController::class, 'update'])
            ->name('cms.settings.update');

        Route::get('cms/users', [CmsAccessController::class, 'index'])
            ->name('cms.users.index');

        Route::put('cms/users/{userId}', [CmsAccessController::class, 'update'])
            ->whereNumber('userId')
            ->name('cms.users.update');

        Route::get('cms/submissions', [CmsSubmissionController::class, 'index'])
            ->name('cms.submissions.index');

        Route::get('cms/themes', [CmsThemeController::class, 'index'])
            ->name('cms.themes.index');

        Route::post('cms/themes', [CmsThemeController::class, 'store'])
            ->name('cms.themes.store');

        Route::post('cms/themes/{slug}/{version}/activate', [CmsThemeController::class, 'activate'])
            ->where('slug', '[a-z0-9-]+')
            ->where('version', '[0-9A-Za-z.+-]+')
            ->name('cms.themes.activate');

        Route::delete('cms/themes/{slug}/{version}', [CmsThemeController::class, 'destroy'])
            ->where('slug', '[a-z0-9-]+')
            ->where('version', '[0-9A-Za-z.+-]+')
            ->name('cms.themes.destroy');
    });
