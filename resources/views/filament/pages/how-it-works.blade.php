<x-filament-panels::page>
    <div class="fi-page-content-ctn space-y-6 text-sm text-gray-950 dark:text-white">
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Overview</h2>
            <p class="text-gray-600 dark:text-gray-400">
                This app generates a customized Shopify theme from a base theme ZIP. You upload a theme, define your brand, and run an AI agent that composes sections and exports the final template. Below are the steps in order.
            </p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Step 1 – Create a project</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                Go to <strong>Projects</strong> and create a project. Optionally set the <strong>OpenRouter API key</strong> for this project (or set a global key in Settings) so the AI models can run.
            </p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Step 2 – Upload the base theme (Theme Revisions)</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                Go to <strong>Theme Revisions</strong> → <strong>Create</strong>. Select the project and upload a <strong>Theme ZIP</strong> (your Shopify theme export: <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">sections/*.liquid</code>, <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">templates/</code>, etc.).
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                The system will <strong>extract and scan</strong> the theme, read <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">{% schema %}</code> blocks from each section, and build <strong>schemas for all sections and blocks</strong>. These schemas are what the AI uses later to compose the site. Status will move to <strong>Ready</strong> when analysis is done.
            </p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Step 3 – Define your brand (Brand Kits)</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                Go to <strong>Brand Kits</strong> and create or edit a brand for the project. Set:
            </p>
            <ul class="list-disc list-inside text-gray-600 dark:text-gray-400 space-y-1 ml-2">
                <li>Brand name, type, industry, tone of voice, language</li>
                <li><strong>Colors</strong> (primary, secondary, optional background)</li>
                <li><strong>Imagery style</strong> (keywords, vibe) for generated assets</li>
                <li><strong>Logo</strong> (upload) and optional <strong>logo design notes</strong> so the AI aligns the template with your logo</li>
            </ul>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                This data is passed into the AI prompts when generating the theme.
            </p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Step 4 – Start an agent run (Agent Runs)</h2>
            <p class="text-gray-600 dark:text-gray-400 mb-2">
                Go to <strong>Agent Runs</strong> → <strong>Create</strong>. Choose the same <strong>project</strong> and a <strong>theme revision</strong> (the one you uploaded). Optionally add a <strong>creative brief</strong> for extra instructions. Select output format (full ZIP and/or JSON + media).
            </p>
            <p class="text-gray-600 dark:text-gray-400">
                The run will: <strong>plan</strong> the homepage (which sections and order), <strong>compose</strong> each section (settings and blocks from the scanned schemas), <strong>plan media</strong>, <strong>generate placeholder media</strong>, and <strong>export</strong> the theme (including your logo in <code class="px-1 py-0.5 bg-gray-100 dark:bg-gray-800 rounded">assets/</code> if you set one). You can watch progress and logs on the run view.
            </p>
        </div>

        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10 p-6">
            <h2 class="text-lg font-semibold mb-3">Step 5 – Download the result</h2>
            <p class="text-gray-600 dark:text-gray-400">
                When the run is <strong>completed</strong>, use the run’s export/download actions to get the theme ZIP (and optionally the JSON + media archive). That ZIP is your generated theme, built from the base theme’s sections and blocks according to the brand and your creative brief.
            </p>
        </div>

        <div class="rounded-xl bg-gray-50 dark:bg-gray-800/50 ring-1 ring-gray-950/5 dark:ring-white/10 p-4 text-gray-600 dark:text-gray-400">
            <strong>Summary:</strong> Upload theme (Theme Revisions) → Define brand (Brand Kits) → Create run (Agent Runs) → AI plans, composes, and exports → Download the generated theme.
        </div>
    </div>
</x-filament-panels::page>
