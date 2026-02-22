<x-filament-panels::page>
    <div class="fi-page-content-ctn space-y-6 text-sm text-gray-950 dark:text-white">
        {{-- Queue / why Agent Run does nothing --}}
        @if($this->getQueueConnection() === 'database')
            @php $pending = $this->getPendingJobsCount(); @endphp
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-amber-900 dark:text-amber-100">
                <h3 class="font-semibold mb-2">Why does nothing happen after I start an Agent Run?</h3>
                <p class="mb-2">
                    The app is using the <strong>database</strong> queue. When you click "Run" or "Run again", the job is added to the queue but <strong>does not run until a worker process is running</strong>. If no worker is started, the run stays Pending and no JSON/template is built.
                </p>
                <p class="mb-2">
                    <strong>Pending jobs in queue:</strong> {{ $pending }}. {{ $pending > 0 ? 'These jobs are waiting for a worker.' : '' }}
                </p>
                <p class="mb-0">
                    <strong>Fix:</strong> Either (1) on the server run <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded">php artisan queue:work</code> and keep it running (e.g. as a separate process on Railway), or (2) set <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded">QUEUE_CONNECTION=sync</code> in <code class="px-1 py-0.5 bg-amber-100 dark:bg-amber-800 rounded">.env</code> so jobs run immediately in the same request (no worker needed; very long runs may hit timeouts).
                </p>
            </div>
        @endif

        {{-- AI activity logs --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6" wire:poll.10s>
            <h2 class="text-lg font-semibold mb-3">AI activity (recent)</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Logs from agent runs (summary, plan, compose). Updates every 10 seconds.
            </p>
            @php $aiLogs = $this->getAiLogs(); @endphp
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-white/10">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Time</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Run</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Step</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Level</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                        @forelse($aiLogs as $log)
                            <tr class="@if($log->level === 'error') bg-red-50 dark:bg-red-900/10 @elseif($log->level === 'warning') bg-amber-50 dark:bg-amber-900/10 @else bg-white dark:bg-white/5 @endif">
                                <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-600 dark:text-gray-400">
                                    {{ $log->created_at?->format('Y-m-d H:i:s') }}
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <a href="{{ $this->getAgentRunViewUrl($log->agent_run_id) }}" class="text-primary-600 dark:text-primary-400 hover:underline">
                                        #{{ $log->agent_run_id }}
                                    </a>
                                </td>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-400">{{ $log->step_key }}</td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                                        @if($log->level === 'error') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($log->level === 'warning') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                        {{ $log->level }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-md truncate" title="{{ $log->message }}">{{ $log->message }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                    No AI logs yet. Run an Agent Run (e.g. from Agent Runs → Create or Edit → Run again) to see activity here.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($aiLogs->hasPages())
                <div class="mt-4">
                    {{ $aiLogs->links() }}
                </div>
            @endif
        </div>

        {{-- Laravel log tail --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Laravel log (last 200 lines)</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Path: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded text-xs">{{ $this->getLaravelLogPath() }}</code>. Refresh the page to see new lines.
            </p>
            @php
                $laravelLogExists = $this->getLaravelLogExists();
                $laravelLines = $this->getLaravelLogLines();
            @endphp
            @if(!$laravelLogExists)
                <div class="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4 text-amber-800 dark:text-amber-200 text-sm">
                    Log file not found yet. It will be created when the app writes to the log (e.g. when a queue job runs, or an error occurs). On some hosts the path may be different or logs may be sent elsewhere (e.g. stderr). Run an Agent Run and ensure the queue worker is running (<code>php artisan queue:work</code>) to generate log entries.
                </div>
            @else
                <div class="max-h-96 overflow-auto rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-gray-900/50 p-3">
                    <pre class="text-xs font-mono text-gray-700 dark:text-gray-300 whitespace-pre-wrap break-all">@foreach($laravelLines as $line){{ $line }}{{ "\n" }}@endforeach</pre>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
