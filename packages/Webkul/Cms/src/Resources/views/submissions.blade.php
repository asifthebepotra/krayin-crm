<x-admin::layouts>
    <x-slot:title>
        @lang('cms::app.submissions.title')
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-bold dark:text-white">
                    @lang('cms::app.submissions.title')
                </h1>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    @lang('cms::app.submissions.description')
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                @lang('cms::app.settings.back')
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
            @foreach ([
                'total' => ['Total submissions', 'text-gray-900 dark:text-white'],
                'completed' => ['Leads created', 'text-green-700 dark:text-green-400'],
                'failed' => ['Failed', 'text-red-700 dark:text-red-400'],
                'pending' => ['Processing', 'text-amber-700 dark:text-amber-400'],
            ] as $key => [$label, $color])
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-semibold {{ $color }}">{{ number_format($summary[$key]) }}</p>
                </div>
            @endforeach
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="border-b border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.submissions.form')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.submissions.status')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.submissions.lead')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.submissions.date')</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase text-gray-500">@lang('cms::app.submissions.details')</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($submissions as $submission)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-gray-800 dark:text-white">{{ $submission->form_handle }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ $submission->submission_id }}</p>
                                </td>

                                <td class="px-4 py-4 text-sm">
                                    @php
                                        $statusClasses = [
                                            'completed' => 'text-green-700 dark:text-green-400',
                                            'failed' => 'text-red-700 dark:text-red-400',
                                            'processing' => 'text-amber-700 dark:text-amber-400',
                                            'pending' => 'text-gray-600 dark:text-gray-300',
                                        ];
                                    @endphp
                                    <span class="font-medium {{ $statusClasses[$submission->status] ?? 'text-gray-600' }}">
                                        {{ ucfirst($submission->status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-sm">
                                    @if ($submission->crm_lead_id)
                                        <a href="{{ route('admin.leads.view', $submission->crm_lead_id) }}" class="text-blue-600 hover:underline dark:text-blue-400">
                                            #{{ $submission->crm_lead_id }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $submission->created_at }}
                                </td>

                                <td class="max-w-sm px-4 py-4 text-xs text-red-700 dark:text-red-300">
                                    {{ $submission->error_message ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                                    @lang('cms::app.submissions.empty')
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($submissions->hasPages())
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                    {{ $submissions->links() }}
                </div>
            @endif
        </div>
    </div>
</x-admin::layouts>
