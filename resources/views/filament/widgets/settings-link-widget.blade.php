<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between gap-2">
            <div>
                <h3 class="text-lg font-semibold text-gray-950 dark:text-white">OpenRouter API key</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Set a global API key for AI models (used when a project has no key).</p>
            </div>
            <a href="{{ $this->getSettingsUrl() }}"
               class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-primary fi-btn-size-md fi-btn-outlined gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-transparent border border-gray-300 dark:border-white/20 text-gray-950 dark:text-white hover:bg-gray-50 dark:hover:bg-white/5 focus:ring-primary-500/50 dark:focus:ring-primary-400/50">
                Settings
            </a>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
