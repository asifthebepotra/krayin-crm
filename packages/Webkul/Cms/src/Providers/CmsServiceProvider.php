<?php

namespace Webkul\Cms\Providers;

use Illuminate\Support\ServiceProvider;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/cms.php', 'cms');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');
    }

    /**
     * Bootstrap package resources.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'cms');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'cms');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }
}
