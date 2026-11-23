<?php

use Illuminate\Support\Facades\Route;
use Webkul\Cms\Http\Controllers\CmsController;

Route::middleware(['web', 'admin_locale', 'user'])
    ->prefix(trim(config('app.admin_path', 'admin'), '/'))
    ->name('admin.')
    ->group(function () {
        Route::get('cms', [CmsController::class, 'index'])
            ->name('cms.index');
    });
