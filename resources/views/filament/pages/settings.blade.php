<x-filament-panels::page>
    <form wire:submit="saveOpenRouterKey" class="space-y-6">
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">OpenRouter API Key</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Set a global API key for OpenRouter. It will be used when a project does not have its own key. Get your key at <a href="https://openrouter.ai/keys" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 underline">openrouter.ai/keys</a>.
                </p>
                @if($this->hasOpenRouterKey())
                    <p class="text-sm text-success-600 dark:text-success-400 mb-2">A key is configured. Enter a new value below to change it.</p>
                @endif
                <div class="max-w-md">
                    <input type="password"
                           wire:model="openrouter_api_key"
                           placeholder="Enter your API key (leave blank to keep current)"
                           class="fi-input block w-full rounded-lg border-gray-300 dark:border-white/20 dark:bg-white/5 px-3 py-2 text-sm text-gray-950 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400"
                           autocomplete="off" />
                </div>
                <div class="mt-4">
                    <button type="submit"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus:ring-2 rounded-lg fi-btn-color-primary fi-btn-size-md fi-btn-outlined gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-transparent border border-gray-300 dark:border-white/20 text-gray-950 dark:text-white hover:bg-gray-50 dark:hover:bg-white/5 focus:ring-primary-500/50 dark:focus:ring-primary-400/50 fi-ac-action">
                        Save OpenRouter key
                    </button>
                </div>
            </div>
        </div>
    </form>
</x-filament-panels::page>
