<x-admin::layouts>
    <x-slot:title>
        @lang('cms::app.users.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-bold dark:text-white">
                    @lang('cms::app.users.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.users.description')
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                @lang('cms::app.settings.back')
            </a>
        </div>

        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900 dark:border-blue-900 dark:bg-blue-950 dark:text-blue-200">
            @lang('cms::app.users.notice')
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.users.employee')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.users.status')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.users.cms_role')</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($users as $user)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-gray-800 dark:text-white">{{ $user->name }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $user->email }}</p>
                                </td>

                                <td class="px-4 py-4 text-sm">
                                    @if ($user->status)
                                        <span class="text-green-700 dark:text-green-400">@lang('cms::app.users.active')</span>
                                    @else
                                        <span class="text-red-700 dark:text-red-400">@lang('cms::app.users.inactive')</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4">
                                    <form
                                        method="POST"
                                        action="{{ route('admin.cms.users.update', $user->id) }}"
                                        class="flex min-w-[260px] items-center gap-2"
                                    >
                                        @csrf
                                        @method('PUT')

                                        <select
                                            name="role"
                                            @disabled(! $user->status)
                                            class="min-h-[38px] flex-1 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-700 dark:border-gray-700 dark:bg-gray-950 dark:text-white"
                                        >
                                            <option value="">@lang('cms::app.users.no_access')</option>

                                            @foreach ($roles as $handle => $label)
                                                <option
                                                    value="{{ $handle }}"
                                                    @selected(($assignments[$user->id] ?? null) === $handle)
                                                >
                                                    {{ $label }}
                                                </option>
                                            @endforeach
                                        </select>

                                        <button
                                            type="submit"
                                            @disabled(! $user->status)
                                            class="secondary-button"
                                        >
                                            @lang('cms::app.users.save')
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500">
                                    @lang('cms::app.users.empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
