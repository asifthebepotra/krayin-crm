<?php

namespace Webkul\Cms\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Statamic\Http\Controllers\FrontendController;
use Webkul\Cms\Http\Middleware\EnsureActiveKrayinUser;
use Webkul\Cms\Http\Middleware\ResolveCmsTheme;
use Webkul\Cms\Listeners\CreateKrayinLeadFromStatamicForm;
use Webkul\Cms\Models\CmsUser;
use Webkul\Cms\Tags\CmsTheme as CmsThemeTag;

class CmsServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration and shared identity settings.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__).'/Config/cms.php', 'cms');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(dirname(__DIR__).'/Config/acl.php', 'acl');

        /*
         * Statamic resolves its Eloquent user model from the CP guard's
         * Laravel provider. Keep Krayin's existing "user" guard and use a
         * subclass which adds only Statamic-specific casts.
         */
        config([
            'auth.providers.users.model' => CmsUser::class,
            'statamic.users.repository' => 'eloquent',
            'statamic.users.guards.cp' => 'user',
            'statamic.users.guards.web' => 'user',
            'statamic.users.tables.users' => 'users',
            'statamic.users.tables.role_user' => 'statamic_role_user',
            'statamic.users.tables.roles' => false,
            'statamic.users.tables.group_user' => 'statamic_group_user',
            'statamic.users.tables.groups' => false,
            'statamic.users.two_factor_enabled' => false,
        ]);
    }

    /**
     * Bootstrap package resources and integration middleware.
     */
    public function boot(Router $router): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'cms');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'cms');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->publishes([
            __DIR__.'/../Resources/users/roles.yaml' => resource_path('users/roles.yaml'),
        ], 'krayin-cms');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Webkul\Cms\Console\Commands\InstallCmsCommand::class,
            ]);
        }

        if (! class_exists(\Statamic\Statamic::class)) {
            return;
        }

        /*
         * Wait until Statamic's providers have registered their extension
         * bindings, route groups and public frontend routes.
         */
        $this->app->booted(function () use ($router) {
            Event::listen(
                \Statamic\Events\FormSubmitted::class,
                CreateKrayinLeadFromStatamicForm::class
            );

            CmsThemeTag::register();

            $router->prependMiddlewareToGroup(
                'statamic.cp.authenticated',
                EnsureActiveKrayinUser::class
            );

            /*
             * Krayin's legacy GET / route redirects to admin login. Register
             * Statamic's frontend controller at the root after all providers.
             */
            if (
                config('cms.public_homepage') === 'statamic'
                && config('statamic.routes.enabled', true)
            ) {
                Route::middleware(config('statamic.routes.middleware', 'web'))
                    ->get('/', [FrontendController::class, 'index'])
                    ->name('cms.public.home');
            }

            /*
             * Resolve theme views only for public Statamic frontend requests.
             * CRM admin and Statamic Control Panel views keep their own paths.
             */
            $frontendRoute = $router->getRoutes()->getByName('statamic.site');

            if ($frontendRoute) {
                $frontendRoute->middleware(ResolveCmsTheme::class);
            }

            $homepageRoute = $router->getRoutes()->getByName('cms.public.home');

            if ($homepageRoute) {
                $homepageRoute->middleware(ResolveCmsTheme::class);
            }
        });
    }
}
