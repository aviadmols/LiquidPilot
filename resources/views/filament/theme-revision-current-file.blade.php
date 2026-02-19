<div class="fi-section-content-ctn space-y-2">
    <p class="text-sm text-gray-600 dark:text-gray-400">
        <span class="font-medium">Current file:</span>
        {{ $record->original_filename ?? 'No file uploaded yet' }}
    </p>
    @if(!empty($zipPath))
        <p class="text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Stored path (library):</span>
            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded">{{ $zipPath }}</code>
        </p>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            <span class="font-medium">Full path (ZIP file):</span>
            <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 rounded break-all">{{ $zipFullPath ?? '-' }}</code>
        </p>
        @if(isset($zipFullPath) && !is_file($zipFullPath))
            <p class="text-sm text-amber-600 dark:text-amber-400">
                File not found on disk. Re-upload the ZIP with "Upload ZIP" above.
            </p>
        @endif
    @else
        <p class="text-sm text-amber-600 dark:text-amber-400">
            No ZIP linked. Use "Upload ZIP" above, then "Run scan now".
        </p>
    @endif
</div>
