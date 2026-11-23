<x-admin::layouts>
    <x-slot:title>
        @lang('cms::app.themes.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-bold dark:text-white">
                    @lang('cms::app.themes.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.themes.description')
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                @lang('cms::app.settings.back')
            </a>
        </div>

        @if (! $ready)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-5 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-200">
                @lang('cms::app.themes.install_required')
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-gray-900">
                <h2 class="font-semibold dark:text-white">@lang('cms::app.themes.upload_title')</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">@lang('cms::app.themes.upload_help')</p>

                <form method="POST" action="{{ route('admin.cms.themes.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf

                    <div class="grid flex-1 gap-2">
                        <label for="theme_zip" class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            @lang('cms::app.themes.theme_zip')
                        </label>
                        <input
                            id="theme_zip"
                            name="theme_zip"
                            type="file"
                            accept=".zip,application/zip"
                            required
                            class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                        />
                        @error('theme_zip')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit" class="primary-button">
                        @lang('cms::app.themes.install')
                    </button>
                </form>
            </div>

            @error('theme')
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    {{ $message }}
                </div>
            @enderror

            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                @forelse ($themes as $theme)
                    @php
                        $isActive = $active
                            && $active->slug === $theme->slug
                            && $active->version === $theme->version;
                        $preview = $theme->asset_path.'/preview.png';
                        if (! is_file($preview)) {
                            $preview = $theme->asset_path.'/preview.jpg';
                        }
                        if (! is_file($preview)) {
                            $preview = $theme->asset_path.'/preview.webp';
                        }
                        $previewUrl = is_file($preview)
                            ? asset('themes/'.$theme->slug.'/'.$theme->version.'/'.basename($preview))
                            : null;
                    @endphp

                    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                        @if ($previewUrl)
                            <img src="{{ $previewUrl }}" alt="{{ $theme->name }}" class="h-48 w-full object-cover" />
                        @else
                            <div class="flex h-32 items-center justify-center bg-gray-50 text-sm text-gray-400 dark:bg-gray-950">
                                @lang('cms::app.themes.no_preview')
                            </div>
                        @endif

                        <div class="flex flex-col gap-3 p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $theme->name }}</h2>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $theme->slug }} · v{{ $theme->version }}
                                        @if ($theme->author)
                                            · {{ $theme->author }}
                                        @endif
                                    </p>
                                </div>

                                @if ($isActive)
                                    <span class="rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-800 dark:bg-green-900 dark:text-green-200">
                                        @lang('cms::app.themes.active')
                                    </span>
                                @endif
                            </div>

                            @if ($theme->description)
                                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $theme->description }}</p>
                            @endif

                            <div class="flex flex-wrap gap-2">
                                @unless ($isActive)
                                    <form method="POST" action="{{ route('admin.cms.themes.activate', [$theme->slug, $theme->version]) }}">
                                        @csrf
                                        <button type="submit" class="primary-button">
                                            @lang('cms::app.themes.activate')
                                        </button>
                                    </form>
                                @endunless

                                <form method="POST" action="{{ route('admin.cms.themes.destroy', [$theme->slug, $theme->version]) }}" onsubmit="return confirm('{{ __('cms::app.themes.remove_confirm') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="secondary-button" @disabled($isActive)>
                                        @lang('cms::app.themes.remove')
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 xl:col-span-2">
                        @lang('cms::app.themes.empty')
                    </div>
                @endforelse
            </div>
        @endif
    </div>
</x-admin::layouts>
