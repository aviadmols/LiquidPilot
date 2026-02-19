<div>
    @if($this->shouldPoll)
        <div wire:poll.3s class="text-sm text-gray-500 dark:text-gray-400 mb-2">
            Auto-refreshing every 3s until analysis completes.
        </div>
    @endif

    @php $revision = $this->revision; @endphp
    @if(!$revision)
        <p class="text-gray-500 dark:text-gray-400">Revision not found.</p>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">
            <strong>Status:</strong>
            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium
                @if($revision->status === 'ready') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                @elseif($revision->status === 'failed') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                @elseif($revision->status === 'analyzing') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                {{ $revision->status }}
            </span>
        </p>
        @if(filled($revision->error))
            <p class="text-sm text-red-600 dark:text-red-400 mb-2">{{ $revision->error }}</p>
        @endif

        @php $steps = $this->steps; @endphp
        @if(empty($steps))
            <p class="text-gray-500 dark:text-gray-400">No steps yet. Analysis will start shortly (ensure the queue worker is running).</p>
        @else
            <ul class="space-y-1.5 text-sm">
                @foreach($steps as $entry)
                    <li class="flex gap-2 items-start">
                        <span class="text-gray-400 dark:text-gray-500 shrink-0">@if(!empty($entry['at'])){{ \Carbon\Carbon::parse($entry['at'])->format('H:i:s') }}@else &nbsp; @endif</span>
                        <span class="font-medium text-gray-700 dark:text-gray-300">{{ $entry['step'] ?? '-' }}</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $entry['message'] ?? '' }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    @endif
</div>
