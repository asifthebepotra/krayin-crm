<x-admin::layouts>
    <x-slot:title>
        Website Settings
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-semibold dark:text-white">Website Settings</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Configure the primary identity and SEO defaults for the public website.
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                Back to CMS
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-300 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @unless ($tablesReady)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                Run <code>php artisan migrate</code> before saving website settings.
            </div>
        @endunless

        <form
            method="POST"
            action="{{ route('admin.cms.settings.update') }}"
            class="flex flex-col gap-6 rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900"
        >
            @csrf
            @method('PUT')

            <div class="grid gap-5 md:grid-cols-2">
                <div>
                    <label for="site_name" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Website Name
                    </label>
                    <input
                        id="site_name"
                        name="site_name"
                        type="text"
                        required
                        maxlength="150"
                        value="{{ old('site_name', $settings->site_name ?? config('app.name')) }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                    @error('site_name')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="primary_domain" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Primary Domain
                    </label>
                    <input
                        id="primary_domain"
                        name="primary_domain"
                        type="text"
                        placeholder="www.example.com"
                        maxlength="253"
                        value="{{ old('primary_domain', $settings->primary_domain ?? '') }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                    <p class="mt-1 text-xs text-gray-500">Enter the hostname only, without https:// or a path.</p>
                    @error('primary_domain')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="default_locale" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Default Locale
                    </label>
                    <input
                        id="default_locale"
                        name="default_locale"
                        type="text"
                        required
                        maxlength="20"
                        placeholder="en or en-US"
                        value="{{ old('default_locale', $settings->default_locale ?? config('app.locale', 'en')) }}"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                    @error('default_locale')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="border-t border-gray-200 pt-5 dark:border-gray-800">
                <h2 class="font-semibold text-gray-900 dark:text-white">Default SEO</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Used as fallback metadata when a page or article does not define its own SEO values.
                </p>

                <div class="mt-4 grid gap-5">
                    <div>
                        <label for="seo_title" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Default Meta Title
                        </label>
                        <input
                            id="seo_title"
                            name="seo_title"
                            type="text"
                            maxlength="160"
                            value="{{ old('seo_title', $settings->seo_title ?? '') }}"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >
                        @error('seo_title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="seo_description" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Default Meta Description
                        </label>
                        <textarea
                            id="seo_description"
                            name="seo_description"
                            rows="3"
                            maxlength="320"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                        >{{ old('seo_description', $settings->seo_description ?? '') }}</textarea>
                        @error('seo_description')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="flex justify-end border-t border-gray-200 pt-5 dark:border-gray-800">
                <button type="submit" class="primary-button" @disabled(! $tablesReady)>
                    Save Website Settings
                </button>
            </div>
        </form>
    </div>
</x-admin::layouts>