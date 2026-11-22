<x-admin::layouts>
    <x-slot:title>
        CMS Themes
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-semibold dark:text-white">Website Themes</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Install and activate Antlers themes for the public website.
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

        @if ($errors->has('theme'))
            <div class="rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                {{ $errors->first('theme') }}
            </div>
        @endif

        @unless ($tablesReady)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                Run <code>php artisan migrate</code> before installing or activating themes.
            </div>
        @endunless

        <section class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
            <h2 class="font-semibold text-gray-900 dark:text-white">Upload Theme ZIP</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Upload a validated ZIP containing theme.json, Antlers templates and static assets. Maximum archive size: 15 MB.
            </p>

            <form
                method="POST"
                action="{{ route('admin.cms.themes.store') }}"
                enctype="multipart/form-data"
                class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end"
            >
                @csrf

                <div class="flex-1">
                    <label for="theme_archive" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Theme archive
                    </label>
                    <input
                        id="theme_archive"
                        name="theme_archive"
                        type="file"
                        accept=".zip,application/zip"
                        required
                        class="w-full rounded-md border border-gray-300 p-2 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                    >
                    @error('theme_archive')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <button type="submit" class="primary-button">Install theme</button>
            </form>
        </section>

        <section class="flex flex-col gap-3">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 dark:text-white">Installed Themes</h2>

                @if ($activeTheme)
                    <span class="text-xs text-green-700 dark:text-green-300">
                        Active: {{ $activeTheme->name }} {{ $activeTheme->version }}
                    </span>
                @else
                    <span class="text-xs text-gray-500 dark:text-gray-400">Using the built-in starter templates</span>
                @endif
            </div>

            @forelse ($themes as $theme)
                <article class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="flex items-start gap-4">
                            @if (file_exists(public_path("themes/{$theme->slug}/{$theme->version}/preview.png")))
                                <img
                                    src="{{ asset("themes/{$theme->slug}/{$theme->version}/preview.png") }}"
                                    alt="{{ $theme->name }} preview"
                                    class="h-20 w-28 rounded-md border border-gray-200 object-cover dark:border-gray-700"
                                >
                            @else
                                <div class="flex h-20 w-28 items-center justify-center rounded-md bg-gray-100 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                    No preview
                                </div>
                            @endif

                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $theme->name }}</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    {{ $theme->slug }} · v{{ $theme->version }}
                                    @if ($theme->author)
                                        · {{ $theme->author }}
                                    @endif
                                </p>
                                @if ($theme->description)
                                    <p class="mt-2 max-w-2xl text-sm text-gray-600 dark:text-gray-300">{{ $theme->description }}</p>
                                @endif
                                <p class="mt-2 text-xs text-gray-400">
                                    Installed {{ optional($theme->installed_at)->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <a
                                href="{{ route('admin.cms.themes.preview', $theme->id) }}"
                                target="_blank"
                                rel="noopener"
                                class="secondary-button"
                            >
                                Preview
                            </a>

                            @if ($activeTheme && $activeTheme->id === $theme->id)
                                <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-900 dark:text-green-200">
                                    Active
                                </span>

                                <form method="POST" action="{{ route('admin.cms.themes.deactivate') }}">
                                    @csrf
                                    <button type="submit" class="secondary-button">Use built-in theme</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.cms.themes.activate', $theme->id) }}">
                                    @csrf
                                    <button type="submit" class="primary-button">Activate</button>
                                </form>

                                <form
                                    method="POST"
                                    action="{{ route('admin.cms.themes.destroy', $theme->id) }}"
                                    onsubmit="return confirm('Remove this installed theme?');"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="secondary-button">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No ZIP themes installed yet. The built-in starter templates are currently in use.
                </div>
            @endforelse
        </section>

        <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
            Theme packages are treated as untrusted input. Only Antlers templates and approved static assets are accepted. PHP, Composer files, route files and service providers are rejected.
        </div>
    </div>
</x-admin::layouts>