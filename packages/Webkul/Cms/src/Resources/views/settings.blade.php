<x-admin::layouts>
    <x-slot:title>
        @lang('cms::app.settings.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-bold dark:text-white">
                    @lang('cms::app.settings.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.settings.description')
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                @lang('cms::app.settings.back')
            </a>
        </div>

        <form method="POST" action="{{ route('admin.cms.settings.update') }}">
            @csrf
            @method('PUT')

            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="grid gap-2">
                        <label for="site_name" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.settings.site_name')
                        </label>

                        <input
                            id="site_name"
                            name="site_name"
                            type="text"
                            maxlength="150"
                            value="{{ old('site_name', $settings->site_name) }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />

                        @error('site_name')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="primary_domain" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.settings.primary_domain')
                        </label>

                        <input
                            id="primary_domain"
                            name="primary_domain"
                            type="text"
                            maxlength="253"
                            placeholder="www.example.com"
                            value="{{ old('primary_domain', $settings->primary_domain) }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @lang('cms::app.settings.primary_domain_help')
                        </p>

                        @error('primary_domain')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="default_locale" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.settings.default_locale')
                        </label>

                        <input
                            id="default_locale"
                            name="default_locale"
                            type="text"
                            maxlength="12"
                            required
                            value="{{ old('default_locale', $settings->default_locale) }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />

                        @error('default_locale')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2">
                        <label for="timezone" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.settings.timezone')
                        </label>

                        <input
                            id="timezone"
                            name="timezone"
                            type="text"
                            required
                            value="{{ old('timezone', $settings->timezone) }}"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />

                        @error('timezone')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid gap-2 md:col-span-2">
                        <label for="default_lead_owner_id" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.settings.default_lead_owner')
                        </label>

                        <select
                            id="default_lead_owner_id"
                            name="default_lead_owner_id"
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        >
                            <option value="">@lang('cms::app.settings.select_lead_owner')</option>

                            @foreach ($employees as $employee)
                                <option
                                    value="{{ $employee->id }}"
                                    @selected((string) old('default_lead_owner_id', $settings->default_lead_owner_id) === (string) $employee->id)
                                >
                                    {{ $employee->name }} — {{ $employee->email }}
                                </option>
                            @endforeach
                        </select>

                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            @lang('cms::app.settings.default_lead_owner_help')
                        </p>

                        @error('default_lead_owner_id')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="primary-button">
                        @lang('cms::app.settings.save')
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-admin::layouts>
