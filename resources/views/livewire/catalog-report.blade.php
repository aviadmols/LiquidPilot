<div class="space-y-6">
    @php $report = $this->report; @endphp
    @if(empty($report['sections']))
        <p class="text-gray-500 dark:text-gray-400">No catalog data. Scan the theme first.</p>
    @else
        @foreach($report['sections'] as $index => $sec)
            <div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 shadow-sm">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $index + 1 }}. {{ $sec['name'] }} <code class="text-sm font-normal text-gray-600 dark:text-gray-400">{{ $sec['handle'] }}</code>
                </h3>
                <ul class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <li><strong>Enabled on:</strong> {{ is_array($sec['enabled_on']) ? implode(', ', $sec['enabled_on']) : ($sec['enabled_on'] ?? '-') }}</li>
                    <li><strong>Max blocks:</strong> {{ $sec['max_blocks'] ?? '-' }}</li>
                    <li><strong>Settings:</strong> {{ $sec['settings_count'] }}</li>
                    <li><strong>Blocks:</strong> {{ $sec['blocks_count'] }}</li>
                    <li><strong>Block types:</strong> {{ implode(', ', $sec['block_types'] ?: ['-']) }}</li>
                </ul>

                <h4 class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">Settings / design elements</h4>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-sm text-gray-600 dark:text-gray-400">
                    @foreach($sec['settings'] as $s)
                        <li><code>{{ $s['id'] ?? '-' }}</code> ({{ $s['type'] ?? '' }}): {{ $s['label'] ?? '' }}@if(!empty($s['options_summary'])) — {{ $s['options_summary'] }}@endif</li>
                    @endforeach
                </ul>

                <h4 class="mt-4 text-sm font-medium text-gray-700 dark:text-gray-300">Blocks</h4>
                <ul class="mt-1 list-disc list-inside space-y-0.5 text-sm text-gray-600 dark:text-gray-400">
                    @foreach($sec['blocks'] as $b)
                        <li><strong>{{ $b['type'] }}</strong> — {{ $b['name'] }} ({{ $b['settings_count'] }} settings)</li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    @endif
</div>
