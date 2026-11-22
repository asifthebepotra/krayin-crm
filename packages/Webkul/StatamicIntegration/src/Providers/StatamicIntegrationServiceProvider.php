<?php

namespace Webkul\StatamicIntegration\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Statamic\Events\SubmissionCreated;
use Statamic\Http\Controllers\FrontendController;
use Statamic\Statamic;
use Webkul\StatamicIntegration\Console\Commands\InstallStarterContent;
use Webkul\StatamicIntegration\Http\Middleware\ApplyActiveStatamicTheme;
use Webkul\StatamicIntegration\Http\Middleware\EnsureKrayinCmsUserIsActive;
use Webkul\StatamicIntegration\Listeners\QueueStatamicFormSubmission;
use Webkul\StatamicIntegration\Models\CmsUser;

class StatamicIntegrationServiceProvider extends ServiceProvider
{
    /**
     * Register package configuration and the shared Krayin user model.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../Config/cms.php', 'statamic_integration');

        $this->mergeConfigFrom(__DIR__.'/../Config/menu.php', 'menu.admin');

        $this->mergeConfigFrom(__DIR__.'/../Config/acl.php', 'acl');

        /*
         * Statamic's Eloquent repository resolves its model from the provider
         * selected by the CP guard. Use a compatible subclass of Krayin's
         * existing User model so CRM authentication and relationships remain
         * the same while Statamic gets its JSON/datetime casts.
         */
        if (class_exists(Statamic::class)) {
            config()->set('auth.providers.users.model', CmsUser::class);

            Auth::forgetGuards();
        }
    }

    /**
     * Bootstrap the Krayin CMS module.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'statamic_integration');

        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'statamic_integration');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        Event::listen(SubmissionCreated::class, QueueStatamicFormSubmission::class);

        $this->registerPublicHomepageRoute();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallStarterContent::class,
            ]);
        }

        $this->app->booted(function () {
            $this->configureStatamicIdentity();
        });

        $this->app->register(ModuleServiceProvider::class);
    }

    /**
     * Configure Statamic to use Krayin's existing session guard and its
     * separate CMS role/group relationship tables.
     */
    protected function configureStatamicIdentity(): void
    {
        if (! class_exists(Statamic::class)) {
            return;
        }

        config()->set('statamic.users.repository', 'eloquent');
        config()->set('statamic.users.guards.cp', 'user');
        config()->set('statamic.users.guards.web', 'user');

        config()->set('statamic.users.tables.role_user', 'statamic_role_user');
        config()->set('statamic.users.tables.group_user', 'statamic_group_user');
        config()->set('statamic.users.tables.roles', false);
        config()->set('statamic.users.tables.groups', false);

        /*
         * Website media uses its own public disk and directory. This keeps
         * CMS assets separate from Krayin's CRM uploads and profile images.
         */
        config()->set('filesystems.disks.cms-assets', [
            'driver' => 'local',
            'root' => public_path('cms-assets'),
            'url' => '/cms-assets',
            'visibility' => 'public',
            'throw' => false,
        ]);

        /*
         * Krayin's user table has no Statamic two-factor columns. Keep this
         * disabled until an explicit, reviewed migration adds that feature.
         */
        config()->set('statamic.users.two_factor_enabled', false);
        config()->set('statamic.users.wizard_invitation', false);

        $router = $this->app->make(Router::class);

        $router->prependMiddlewareToGroup(
            'statamic.cp.authenticated',
            EnsureKrayinCmsUserIsActive::class
        );

        $router->pushMiddlewareToGroup(
            config('statamic.routes.middleware', 'web'),
            ApplyActiveStatamicTheme::class
        );
    }

    /**
     * Krayin's front package registers "/" as a redirect to the CRM login.
     * Once Statamic is installed, the public website must own the domain root.
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