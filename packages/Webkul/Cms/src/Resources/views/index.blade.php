<x-admin::layouts>
    <x-slot:title>
        @lang('cms::app.general.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-bold dark:text-white">
                    @lang('cms::app.general.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.general.description')
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.cms.settings.edit') }}" class="secondary-button">
                    @lang('cms::app.general.manage_settings')
                </a>

                @if ($controlPanelEnabled)
                    <a href="{{ $controlPanelUrl }}" class="primary-button">
                        @lang('cms::app.general.open_control_panel')
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Statamic CMS</p>

                <p class="mt-2 text-lg font-semibold dark:text-white">
                    @if ($statamicInstalled)
                        @lang('cms::app.general.statamic_ready')
                    @else
                        @lang('cms::app.general.statamic_missing')
                    @endif
                </p>

                @if ($statamicVersion)
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        @lang('cms::app.general.version'): {{ $statamicVersion }}
                    </p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    @lang('cms::app.general.site_name')
                </p>

                <p class="mt-2 text-lg font-semibold dark:text-white">
                    {{ $settings->site_name ?: config('app.name') }}
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    @lang('cms::app.general.primary_domain')
                </p>

                <p class="mt-2 text-lg font-semibold dark:text-white">
                    {{ $settings->primary_domain ?: __('cms::app.general.not_configured') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-semibold dark:text-white">
                    @lang('cms::app.general.native_editor')
                </h2>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.general.native_editor_info')
                </p>

                @if ($controlPanelEnabled)
                    <a href="{{ $controlPanelUrl }}" class="secondary-button mt-4">
                        @lang('cms::app.general.open_control_panel')
                    </a>
                @elseif ($statamicInstalled)
                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">
                        @lang('cms::app.general.control_panel_disabled')
                    </p>
                @else
                    <p class="mt-4 text-sm text-amber-700 dark:text-amber-300">
                        @lang('cms::app.general.installation_hint')
                    </p>
                @endif
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-semibold dark:text-white">
                    @lang('cms::app.general.crm_integration')
                </h2>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.general.crm_integration_info')
                </p>

                <a href="{{ route('admin.cms.settings.edit') }}" class="secondary-button mt-4">
                    @lang('cms::app.general.manage_settings')
                </a>
            </div>
        </div>
    </div>
</x-admin::layouts>
