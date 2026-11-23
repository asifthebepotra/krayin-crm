<?php

use Illuminate\\Support\\Facades\\Route;
use Webkul\\Cms\\Http\\Controllers\\CmsController;
use Webkul\\Cms\\Http\\Controllers\\CmsSettingsController;

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
    });
