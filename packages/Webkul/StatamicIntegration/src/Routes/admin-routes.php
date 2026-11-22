<?php

use Illuminate\Support\Facades\Route;
use Webkul\StatamicIntegration\Http\Controllers\CmsController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(config('app.admin_path'))
    ->name('admin.')
    ->group(function () {
        Route::get('cms', [CmsController::class, 'index'])
            ->name('cms.index');

        Route::get('cms/open', [CmsController::class, 'open'])
            ->name('cms.open');
    });