<?php

namespace Webkul\StatamicIntegration\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Statamic\Http\Controllers\FrontendController;
use Webkul\StatamicIntegration\Console\Commands\InstallStarterContent;

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

        $this->registerPublicHomepageRoute();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallStarterContent::class,
            ]);
        }

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Krayin's front package registers "/" as a redirect to the CRM login.
     * Once Statamic is installed, the public website must own the domain root.
     * Its controller supplies Statamic's normal content lookup and middleware.
     */
    protected function registerPublicHomepageRoute(): void
    {
        if (
            ! class_exists(FrontendController::class)
            || ! config('statamic.routes.enabled', true)
        ) {
            return;
        }

        Route::middleware(config('statamic.routes.middleware', 'web'))
            ->get('/', [FrontendController::class, 'index'])
            ->name('statamic.site.root');
    }
}