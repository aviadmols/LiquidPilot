<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Template Preview – Run #{{ $run->id }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-6 px-4 space-y-6">
        <h1 class="text-xl font-semibold text-gray-700 dark:text-gray-300">Template Preview (Run #{{ $run->id }})</h1>

        @php
            $order = $indexJson['order'] ?? array_keys($indexJson['sections'] ?? []);
            $sections = $indexJson['sections'] ?? [];
        @endphp

        @forelse($order as $sectionId)
            @php
                $section = $sections[$sectionId] ?? null;
                if (!$section) continue;
                $type = $section['type'] ?? $sectionId;
                $settings = $section['settings'] ?? [];
                $blocks = $section['blocks'] ?? [];
                $blockOrder = $section['block_order'] ?? [];
            @endphp
            <section class="rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-white/5 shadow-sm overflow-hidden" data-section-id="{{ $sectionId }}">
                <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-white/10 text-sm font-medium text-gray-600 dark:text-gray-400">
                    {{ $sectionId }} <span class="text-gray-400 dark:text-gray-500">({{ $type }})</span>
                </div>
                <div class="p-4 space-y-3">
                    @if(!empty($settings))
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach($settings as $key => $value)
                                @if(is_string($value) && $value !== '')
                                    @php
                                        $isImage = preg_match('/\.(png|jpe?g|gif|webp|svg)$/i', $value)
                                            || str_contains($value, 'generated')
                                            || str_starts_with($value, 'http');
                                        $imgSrc = $isImage && !str_starts_with($value, 'http')
                                            ? $assetsBaseUrl . '/' . ltrim($value, '/')
                                            : null;
                                    @endphp
                                    @if($imgSrc)
                                        <div class="col-span-2">
                                            <img src="{{ $imgSrc }}" alt="{{ $key }}" class="max-h-48 w-auto object-contain rounded-lg border border-gray-200 dark:border-white/10" onerror="this.style.display='none'">
                                        </div>
                                    @else
                                        <div><span class="text-gray-500 dark:text-gray-400">{{ $key }}:</span> <span class="break-words">{{ Str::limit($value, 80) }}</span></div>
                                    @endif
                                @elseif(is_numeric($value) || is_bool($value))
                                    <div><span class="text-gray-500 dark:text-gray-400">{{ $key }}:</span> {{ $value ? 'true' : 'false' }}</div>
                                @endif
                            @endforeach
                        </div>
                    @endif
                    @if(!empty($blocks))
                        <div class="text-sm text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-100 dark:border-white/5">
                            <span class="font-medium">Blocks:</span> {{ count($blocks) }} block(s)
                        </div>
                    @endif
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6 text-amber-800 dark:text-amber-200">
                No sections in this template. Run the compose step first.
            </div>
        @endforelse
    </div>
</body>
</html>
