<?php

namespace Webkul\StatamicIntegration\Providers;

use Illuminate\Support\ServiceProvider;

class StatamicIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/cms.php', 'statamic_integration');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');
    }

    /**
     * Bootstrap the Krayin CMS module.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'statamic_integration');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'statamic_integration');

        $this->app->register(ModuleServiceProvider::class);
    }
}