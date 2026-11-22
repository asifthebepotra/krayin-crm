<x-admin::layouts>
    <x-slot:title>
        Website Submissions
    </x-slot>

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="grid gap-1">
                <h1 class="text-2xl font-semibold dark:text-white">Website Submissions</h1>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Review Statamic form processing results and their linked Krayin leads.
                </p>
            </div>

            <a href="{{ route('admin.cms.index') }}" class="secondary-button">
                Back to CMS
            </a>
        </div>

        @unless ($tablesReady)
            <div class="rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-800 dark:bg-amber-950 dark:text-amber-100">
                Run <code>php artisan migrate</code> before viewing website submission logs.
            </div>
        @else
            <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Submitted</th>
                                <th class="px-4 py-3">Form</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Attempts</th>
                                <th class="px-4 py-3">CRM Lead</th>
                                <th class="px-4 py-3">Error</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ optional($log->created_at ? \Illuminate\Support\Carbon::parse($log->created_at) : null)->format('Y-m-d H:i') ?? '—' }}
                                    </td>
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $log->form_handle }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusClasses = [
                                                'completed' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                                'failed' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'processing' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
                                                'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200',
                                            ];
                                        @endphp
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClasses[$log->status] ?? 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-200' }}">
                                            {{ ucfirst($log->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                        {{ $log->attempts }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($log->crm_lead_id)
                                            <a
                                                href="{{ route('admin.leads.view', $log->crm_lead_id) }}"
                                                class="text-blue-600 hover:underline dark:text-blue-400"
                                            >
                                                #{{ $log->crm_lead_id }}
                                            </a>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="max-w-md px-4 py-3 text-xs text-red-700 dark:text-red-300">
                                        {{ $log->status === 'failed' ? $log->error_message : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                        No website form submissions have been processed yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($logs->hasPages())
                    <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-800">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        @endunless
    </div>
</x-admin::layouts>