<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Editor – Run #{{ $run->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <header class="border-b border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 px-4 py-2 flex items-center justify-between">
        <h1 class="text-lg font-semibold">Template Editor – Run #{{ $run->id }}</h1>
        <a href="{{ $backUrl }}" class="text-sm text-primary-600 dark:text-primary-400 hover:underline">Back to Run</a>
    </header>

    @if(session('success'))
        <div class="mx-4 mt-2 px-4 py-2 rounded-lg bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mx-4 mt-2 px-4 py-2 rounded-lg bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-200 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex-1 flex min-h-0">
        {{-- Preview iframe --}}
        <div class="flex-1 min-w-0 flex flex-col border-r border-gray-200 dark:border-white/10">
            <div class="px-2 py-1 bg-gray-50 dark:bg-gray-800/50 text-xs text-gray-500 dark:text-gray-400 flex items-center justify-between">
                <span>Preview</span>
                <button type="button" onclick="document.getElementById('preview-frame').src = document.getElementById('preview-frame').src" class="text-primary-600 dark:text-primary-400 hover:underline">Refresh</button>
            </div>
            <iframe id="preview-frame" src="{{ $previewUrl }}" class="flex-1 w-full min-h-[400px] border-0 bg-white dark:bg-gray-900" title="Template preview"></iframe>
        </div>

        {{-- Editor sidebar --}}
        <aside class="w-96 flex-shrink-0 flex flex-col bg-white dark:bg-white/5 border-l border-gray-200 dark:border-white/10 overflow-hidden">
            <div class="px-4 py-2 border-b border-gray-200 dark:border-white/10 font-medium">Sections</div>
            <nav class="flex-1 overflow-y-auto p-2 space-y-1">
                @foreach($order as $sectionId)
                    @php $sec = $sections[$sectionId] ?? []; $type = $sec['type'] ?? $sectionId; @endphp
                    <a href="{{ route('admin.template-editor', ['run' => $run->id, 'section' => $sectionId]) }}"
                       class="block px-3 py-2 rounded-lg text-sm {{ $selectedId === $sectionId ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-800 dark:text-primary-200' : 'hover:bg-gray-100 dark:hover:bg-white/10' }}">
                        {{ $sectionId }} <span class="text-gray-400 dark:text-gray-500">({{ $type }})</span>
                    </a>
                @endforeach
            </nav>

            @if($selectedSection)
                <div class="border-t border-gray-200 dark:border-white/10 p-4 overflow-y-auto max-h-[50vh]">
                    <h2 class="font-medium mb-3">Settings: {{ $selectedId }}</h2>
                    <form method="post" action="{{ route('admin.template-editor.update', ['run' => $run->id]) }}" class="space-y-3">
                        @csrf
                        <input type="hidden" name="section_id" value="{{ $selectedId }}">
                        @php $settings = $selectedSection['settings'] ?? []; @endphp
                        @foreach($settings as $key => $value)
                            <div>
                                <label class="block text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">{{ $key }}</label>
                                @if(is_string($value) && (strlen($value) > 80 || str_contains($value, "\n")))
                                    <textarea name="settings[{{ $key }}]" rows="3" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-3 py-2 text-sm">{{ e($value) }}</textarea>
                                @elseif(is_numeric($value))
                                    <input type="number" name="settings[{{ $key }}]" value="{{ $value }}" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                @elseif(is_bool($value))
                                    <select name="settings[{{ $key }}]" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                        <option value="1" {{ $value ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ !$value ? 'selected' : '' }}>No</option>
                                    </select>
                                @elseif(is_array($value) || is_object($value))
                                    <textarea name="settings[{{ $key }}]" rows="3" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-3 py-2 text-sm font-mono text-sm">{{ e(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) }}</textarea>
                                @else
                                    <input type="text" name="settings[{{ $key }}]" value="{{ is_scalar($value) ? e($value) : '' }}" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-gray-900 px-3 py-2 text-sm">
                                @endif
                            </div>
                        @endforeach
                        @if(empty($settings))
                            <p class="text-sm text-gray-500 dark:text-gray-400">No settings for this section.</p>
                        @else
                            <button type="submit" class="w-full rounded-lg bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white px-4 py-2 text-sm font-medium">
                                Save settings
                            </button>
                        @endif
                    </form>
                </div>
            @endif
        </aside>
    </div>
</body>
</html>
