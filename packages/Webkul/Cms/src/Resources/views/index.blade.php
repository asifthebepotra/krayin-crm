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

            @if ($controlPanelEnabled)
                <a
                    href="{{ $controlPanelUrl }}"
                    class="primary-button"
                >
                    @lang('cms::app.general.open_control_panel')
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    Statamic CMS
                </p>

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
                    @lang('cms::app.general.native_editor')
                </p>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.general.native_editor_info')
                </p>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                    @lang('cms::app.general.crm_integration')
                </p>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.general.crm_integration_info')
                </p>
            </div>
        </div>

        @unless ($statamicInstalled)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
                <h2 class="font-semibold text-amber-900 dark:text-amber-200">
                    @lang('cms::app.general.statamic_missing')
                </h2>

                <p class="mt-2 text-sm text-amber-800 dark:text-amber-300">
                    @lang('cms::app.general.installation_hint')
                </p>
            </div>
        @elseif (! $controlPanelEnabled)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
                <p class="text-sm text-amber-800 dark:text-amber-300">
                    @lang('cms::app.general.control_panel_disabled')
                </p>
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-semibold dark:text-white">
                            @lang('cms::app.general.control_panel_enabled')
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            {{ $controlPanelUrl }}
                        </p>
                    </div>

                    <a
                        href="{{ $controlPanelUrl }}"
                        class="secondary-button"
                    >
                        @lang('cms::app.general.open_control_panel')
                    </a>
                </div>
            </div>
        @endunless
    </div>
</x-admin::layouts>
