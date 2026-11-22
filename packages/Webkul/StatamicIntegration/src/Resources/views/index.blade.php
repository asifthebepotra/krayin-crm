<x-admin::layouts>
    <x-slot:title>
        @lang('statamic_integration::app.dashboard.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-semibold dark:text-white">
                    @lang('statamic_integration::app.dashboard.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('statamic_integration::app.dashboard.description')
                </p>
            </div>

            @if ($controlPanelEnabled)
                <a
                    href="{{ route('admin.cms.open') }}"
                    class="primary-button"
                >
                    @lang('statamic_integration::app.dashboard.open_cms')
                </a>
            @endif
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('statamic_integration::app.dashboard.statamic_engine')
                </p>

                <div class="mt-2 flex items-center gap-2">
                    <span class="h-2.5 w-2.5 rounded-full {{ $statamicInstalled ? 'bg-green-500' : 'bg-amber-500' }}"></span>

                    <span class="font-semibold text-gray-900 dark:text-white">
                        {{ $statamicInstalled ? __('statamic_integration::app.dashboard.installed') : __('statamic_integration::app.dashboard.not_installed') }}
                    </span>
                </div>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    @lang('statamic_integration::app.dashboard.minimum_version'):
                    {{ $minimumVersion }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('statamic_integration::app.dashboard.control_panel')
                </p>

                <p class="mt-2 font-semibold text-gray-900 dark:text-white">
                    {{ $controlPanelEnabled ? __('statamic_integration::app.dashboard.enabled') : __('statamic_integration::app.dashboard.disabled') }}
                </p>

                @if ($controlPanelUrl)
                    <a
                        href="{{ $controlPanelUrl }}"
                        class="mt-2 inline-flex text-sm text-blue-600 hover:underline dark:text-blue-400"
                    >
                        {{ $controlPanelUrl }}
                    </a>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    @lang('statamic_integration::app.dashboard.content_api')
                </p>

                <p class="mt-2 font-semibold text-gray-900 dark:text-white">
                    {{ $contentApiEnabled ? __('statamic_integration::app.dashboard.enabled') : __('statamic_integration::app.dashboard.disabled') }}
                </p>

                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    @lang('statamic_integration::app.dashboard.api_disabled_note')
                </p>
            </div>
        </div>

        @unless ($statamicInstalled)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
                <h2 class="font-semibold text-amber-900 dark:text-amber-100">
                    @lang('statamic_integration::app.dashboard.setup_title')
                </h2>

                <p class="mt-2 text-sm text-amber-800 dark:text-amber-200">
                    @lang('statamic_integration::app.dashboard.setup_description')
                </p>

                <p class="mt-3 rounded bg-white/70 px-3 py-2 font-mono text-xs text-gray-800 dark:bg-black/20 dark:text-gray-100">
                    composer update statamic/cms inertiajs/inertia-laravel --with-all-dependencies
                </p>
            </div>
        @endunless

        @if ($statamicInstalled)
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-semibold text-gray-900 dark:text-white">
                    Install the starter website structure
                </h2>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    This installs the default Pages and Blog collections, blueprints and Antlers templates.
                    Existing files are skipped and will not be overwritten.
                </p>

                <p class="mt-3 rounded bg-gray-50 px-3 py-2 font-mono text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-100">
                    php artisan statamic-integration:install-starter-content
                </p>
            </div>
        @endif

        @if ($controlPanelEnabled)
            <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-semibold text-gray-900 dark:text-white">
                    @lang('statamic_integration::app.dashboard.quick_links')
                </h2>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <a
                        href="{{ $controlPanelUrl.'/collections' }}"
                        class="rounded-md border border-gray-200 p-4 transition hover:border-blue-400 dark:border-gray-700"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">
                            @lang('statamic_integration::app.dashboard.collections')
                        </span>
                    </a>

                    <a
                        href="{{ $controlPanelUrl.'/assets/browse' }}"
                        class="rounded-md border border-gray-200 p-4 transition hover:border-blue-400 dark:border-gray-700"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">
                            @lang('statamic_integration::app.dashboard.assets')
                        </span>
                    </a>

                    <a
                        href="{{ $controlPanelUrl.'/navigation' }}"
                        class="rounded-md border border-gray-200 p-4 transition hover:border-blue-400 dark:border-gray-700"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">
                            @lang('statamic_integration::app.dashboard.navigation')
                        </span>
                    </a>

                    <a
                        href="{{ $controlPanelUrl.'/forms' }}"
                        class="rounded-md border border-gray-200 p-4 transition hover:border-blue-400 dark:border-gray-700"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">
                            @lang('statamic_integration::app.dashboard.forms')
                        </span>
                    </a>

                    <a
                        href="{{ route('admin.cms.themes.index') }}"
                        class="rounded-md border border-gray-200 p-4 transition hover:border-blue-400 dark:border-gray-700"
                    >
                        <span class="font-medium text-gray-900 dark:text-white">
                            Website Themes
                        </span>
                    </a>
                </div>
            </section>
        @endif
    </div>
</x-admin::layouts>