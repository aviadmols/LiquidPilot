<x-filament-panels::page>
    <div class="fi-page-content-ctn max-w-4xl space-y-8 text-sm text-gray-950 dark:text-white">
        <div class="rounded-xl border border-gray-200 dark:border-white/10 bg-white shadow-sm dark:bg-white/5 p-8">
            <div class="border-b border-gray-200 dark:border-white/10 pb-6 mb-6">
                <h2 class="text-xl font-semibold text-gray-950 dark:text-white">OpenRouter API Key</h2>
                <p class="mt-2 text-gray-600 dark:text-gray-400">
                    Global key for AI (OpenRouter). Used when a project has no key. Get a key at
                    <a href="https://openrouter.ai/keys" target="_blank" rel="noopener" class="text-primary-600 dark:text-primary-400 underline">openrouter.ai/keys</a>.
                </p>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                    Or set <code class="px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800">OPENROUTER_API_KEY</code> in server environment.
                </p>
            </div>

            @if($this->keySavedSuccess)
                <div class="mb-6 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200">
                    Key saved. The field below was cleared; the key is still in use.
                </div>
            @endif

            @if($this->hasOpenRouterKey())
                <div class="mb-6 p-4 rounded-lg bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300">
                    A key is stored. Enter a new value below to replace it, or leave blank to keep it.
                </div>
            @else
                <div class="mb-6 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-200">
                    No key stored. Paste your API key below and click Save.
                </div>
            @endif

            <form wire:submit="saveOpenRouterKey" class="space-y-6">
                <div>
                    <label for="openrouter_api_key" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        API key
                    </label>
                    <textarea id="openrouter_api_key"
                              wire:model.live="openrouter_api_key"
                              rows="3"
                              placeholder="Paste your OpenRouter API key here (e.g. sk-or-v1-...)"
                              class="fi-input block w-full rounded-lg border border-gray-300 dark:border-white/20 dark:bg-white/5 px-4 py-3 text-sm text-gray-950 dark:text-white shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:focus:border-primary-400 dark:focus:ring-primary-400 font-mono"
                              autocomplete="off"
                              spellcheck="false"></textarea>
                </div>
                <div class="flex items-center gap-4">
                    <button type="submit"
                            class="fi-btn relative grid-flow-col items-center justify-center font-semibold rounded-lg fi-btn-color-primary fi-btn-size-lg gap-2 px-6 py-3 text-sm inline-grid shadow-sm bg-primary-600 border-0 text-white hover:bg-primary-500 focus:ring-2 focus:ring-primary-500 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus:ring-primary-400">
                        Save OpenRouter key
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-filament-panels::page>
