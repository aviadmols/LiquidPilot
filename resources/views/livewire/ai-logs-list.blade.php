<div wire:poll.3s class="rounded-lg border bg-white dark:bg-gray-800 dark:border-gray-700 p-4">
    <h3 class="text-lg font-semibold mb-3">AI Live Logs</h3>
    <div class="max-h-96 overflow-y-auto space-y-1 text-sm font-mono">
        @forelse($this->logs as $log)
            <div class="flex gap-2 py-1 px-2 rounded
                @if($log->level === 'error') bg-red-100 dark:bg-red-900/20 text-red-800 dark:text-red-200
                @elseif($log->level === 'warning') bg-amber-100 dark:bg-amber-900/20 text-amber-800 dark:text-amber-200
                @else bg-gray-50 dark:bg-gray-700/50 text-gray-700 dark:text-gray-300
                @endif">
                <span class="text-xs shrink-0">{{ $log->created_at->format('H:i:s') }}</span>
                <span class="shrink-0 font-semibold">[{{ $log->step_key ?? '-' }}]</span>
                <span>{{ $log->message }}</span>
            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400">No logs yet.</p>
        @endforelse
    </div>
</div>
