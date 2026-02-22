<?php

namespace App\Domain\Agent;

use App\Models\AgentRun;
use App\Models\AgentStep;
use App\Models\BrandKit;
use App\Models\ProjectSecret;
use App\Models\Setting;
use App\Models\ThemeCatalog;
use App\Models\ThemeRevision;
use App\Models\ThemeSection;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates agent steps: resolve prompts, call OpenRouter, validate outputs, update steps and progress.
 * Used by queued jobs; each job runs one step then may dispatch the next.
 *
 * Side effects: DB writes (AgentStep, AIActionLog, AgentRun.progress), network (OpenRouter).
 */
class AgentRunner
{
    public function __construct(
        private readonly OpenRouterClient $openRouterClient,
        private readonly PromptRenderer $promptRenderer,
        private readonly JsonGuard $jsonGuard,
        private readonly SectionSchemaValidator $sectionSchemaValidator
    ) {}

    /**
     * Run summarize step (map-reduce over catalog). Returns summary for planner.
     * Updates agent_steps and progress; uses AIProgressLogger.
     */
    public function runSummarize(AgentRun $run): array
    {
        $logger = AIProgressLogger::forRun($run->id);
        Log::info('AgentRunner::runSummarize entered', ['agent_run_id' => $run->id]);
        $logger->logStart('summary', 'Summarize step entered', [
            'has_theme_revision' => (bool) $run->themeRevision,
            'theme_revision_id' => $run->theme_revision_id,
        ]);

        $step = $this->createStep($run, 'summary', 'running');
        $run->themeRevision->load('themeCatalog');
        $catalog = $run->themeRevision->themeCatalog?->catalog_json ?? [];
        $sections = $catalog['sections'] ?? [];
        $catalogJson = json_encode($sections);
        $projectId = $run->project_id;

        $chunker = new PromptChunker(2000);
        $chunks = $chunker->chunk($catalogJson);
        Log::info('AgentRunner::runSummarize catalog loaded', [
            'agent_run_id' => $run->id,
            'sections_count' => count($sections),
            'chunks_count' => count($chunks),
        ]);
        $logger->logStart('summary', 'Catalog loaded', [
            'sections_count' => count($sections),
            'chunks_count' => count($chunks),
        ]);

        if (empty($chunks)) {
            $step->update(['status' => 'completed', 'output_json' => ['summary' => []]]);
            $logger->updateProgress(15);
            return ['summary' => []];
        }

        $promptKey = count($chunks) > 1 ? 'THEME_SUMMARY_MAP' : 'THEME_SUMMARY_REDUCE';
        $summaries = [];
        foreach ($chunks as $c) {
            $logger->logChunk('summary', $c['index'], $c['total']);
            $resolved = $this->promptRenderer->resolve($promptKey, $projectId, ['catalog_chunk' => $c['text']]);
            $apiKey = $this->getApiKey($projectId);
            $modelConfig = $resolved['model_config'];
            if (!$apiKey || !$modelConfig) {
                $logger->logError('summary', 'Missing OpenRouter API key or model config for project', [
                    'has_api_key' => (bool) $apiKey,
                    'has_model_config' => (bool) $modelConfig,
                ]);
                $step->update(['status' => 'failed', 'logs_text' => 'No API key or model config']);
                throw new \RuntimeException('Missing OpenRouter API key or model config for project');
            }
            $start = microtime(true);
            $logger->logStart('summary', 'Calling AI', ['prompt_key' => $promptKey, 'model' => $modelConfig->model_name]);
            $response = $this->openRouterClient->chat($apiKey, $modelConfig->model_name, [
                ['role' => 'user', 'content' => $resolved['prompt_text']],
            ], [
                'temperature' => $modelConfig->temperature,
                'max_tokens' => $modelConfig->max_tokens,
                'json_mode' => $modelConfig->json_mode,
            ]);
            $durationMs = (int) ((microtime(true) - $start) * 1000);
            $logger->logEnd('summary', 'Response received', ['duration_ms' => $durationMs, 'tokens' => $response['usage'] ?? null]);
            $decoded = $this->jsonGuard->decode($response['content']);
            if ($decoded !== null) {
                $summaries[] = $decoded;
            }
        }

        $finalSummary = count($summaries) > 1
            ? $this->reduceSummaries($run, $summaries)
            : ($summaries[0] ?? ['summary' => []]);
        $step->update(['status' => 'completed', 'output_json' => $finalSummary]);
        $logger->updateProgress(15);
        return $finalSummary;
    }

    private function reduceSummaries(AgentRun $run, array $summaries): array
    {
        $resolved = $this->promptRenderer->resolve('THEME_SUMMARY_REDUCE', $run->project_id, [
            'summaries' => json_encode($summaries),
        ]);
        $apiKey = $this->getApiKey($run->project_id);
        $modelConfig = $resolved['model_config'];
        if (!$apiKey || !$modelConfig) {
            return ['summary' => $summaries, 'section_handles' => []];
        }
        $response = $this->openRouterClient->chat($apiKey, $modelConfig->model_name, [
            ['role' => 'user', 'content' => $resolved['prompt_text']],
        ], ['temperature' => $modelConfig->temperature, 'max_tokens' => $modelConfig->max_tokens, 'json_mode' => true]);
        $decoded = $this->jsonGuard->decode($response['content']);
        return $decoded ?? ['summary' => $summaries];
    }

    /**
     * Run plan step: choose sections for homepage. Validates output against catalog handles.
     */
    public function runPlan(AgentRun $run, array $catalogSummary): array
    {
        $logger = AIProgressLogger::forRun($run->id);
        $step = $this->createStep($run, 'plan', 'running');
        $handles = $run->themeRevision->themeSections()->pluck('handle')->all();
        $brand = $run->resolveBrandKit();
        $brandJson = $brand ? json_encode($brand->toArray()) : '{}';

        $creativeBrief = $run->creative_brief && trim($run->creative_brief) !== '' ? trim($run->creative_brief) : 'None';
        $resolved = $this->promptRenderer->resolve('HOMEPAGE_PLAN', $run->project_id, [
            'catalog_summary' => json_encode($catalogSummary),
            'section_handles' => json_encode($handles),
            'brand' => $brandJson,
            'creative_brief' => $creativeBrief,
        ]);
        $apiKey = $this->getApiKey($run->project_id);
        $modelConfig = $resolved['model_config'];
        if (!$apiKey || !$modelConfig) {
            throw new \RuntimeException('Missing API key or model config');
        }
        $logger->logStart('plan', 'Calling AI', ['model' => $modelConfig->model_name]);
        $response = $this->openRouterClient->chat($apiKey, $modelConfig->model_name, [
            ['role' => 'user', 'content' => $resolved['prompt_text']],
        ], ['temperature' => $modelConfig->temperature, 'max_tokens' => $modelConfig->max_tokens, 'json_mode' => true]);
        $logger->logEnd('plan', 'Response received', ['usage' => $response['usage']]);

        $plan = $this->jsonGuard->parseAndValidate($response['content'], [
            'allowed_section_handles' => $handles,
            'required_keys' => ['sections'],
        ]);
        $logger->logValidation('plan', true);
        $step->update(['status' => 'completed', 'output_json' => $plan]);
        $logger->updateProgress(35);
        return $plan;
    }

    /**
     * Run compose step for one or all sections; returns full index.json structure.
     */
    public function runCompose(AgentRun $run, array $plan): array
    {
        $logger = AIProgressLogger::forRun($run->id);
        $step = $this->createStep($run, 'compose', 'running');
        $sections = $plan['sections'] ?? [];
        if (is_array($sections) && isset($sections[0]) && is_string($sections[0])) {
            $sectionHandles = $sections;
        } else {
            $sectionHandles = array_map(fn ($s) => $s['type'] ?? $s['handle'] ?? $s, $sections);
        }
        $themeRevision = $run->themeRevision;
        $built = ['sections' => [], 'order' => []];
        $order = [];
        foreach ($sectionHandles as $i => $handle) {
            $sectionModel = $themeRevision->themeSections()->where('handle', $handle)->first();
            if (!$sectionModel) {
                continue;
            }
            $schema = $sectionModel->schema_json ?? [];
            $creativeBrief = $run->creative_brief && trim($run->creative_brief) !== '' ? trim($run->creative_brief) : 'None';
            $resolved = $this->promptRenderer->resolve('SECTION_COMPOSE', $run->project_id, [
                'section_handle' => $handle,
                'section_schema' => json_encode($schema),
                'brand' => json_encode($run->resolveBrandKit()?->toArray() ?? []),
                'creative_brief' => $creativeBrief,
            ]);
            $apiKey = $this->getApiKey($run->project_id);
            $modelConfig = $resolved['model_config'];
            if (!$apiKey || !$modelConfig) {
                throw new \RuntimeException('Missing API key or model config');
            }
            $response = $this->openRouterClient->chat($apiKey, $modelConfig->model_name, [
                ['role' => 'user', 'content' => $resolved['prompt_text']],
            ], ['json_mode' => true, 'max_tokens' => $modelConfig->max_tokens]);
            $rawContent = $response['content'] ?? '';
            $sectionPayload = $this->jsonGuard->decode($rawContent);
            if ($sectionPayload === null) {
                $sectionPayload = $this->retryComposeWithJsonFix($run, $rawContent, $apiKey);
            }
            $validated = $sectionPayload !== null
                ? $this->sectionSchemaValidator->validate($schema, $sectionPayload)
                : ['settings' => [], 'blocks' => [], 'block_order' => []];
            $id = 'section_' . ($i + 1);
            $built['sections'][$id] = [
                'type' => $handle,
                'settings' => $validated['settings'],
                'blocks' => $validated['blocks'],
                'block_order' => $validated['block_order'],
            ];
            $order[] = $id;
        }
        $built['order'] = $order;
        $step->update(['status' => 'completed', 'output_json' => $built]);
        $logger->updateProgress(55);
        return $built;
    }

    /**
     * One retry using JSON_FIX when SECTION_COMPOSE returned invalid JSON.
     * @return array{settings: array, blocks: array, block_order: array}|null
     */
    private function retryComposeWithJsonFix(AgentRun $run, string $invalidJson, string $apiKey): ?array
    {
        $resolved = $this->promptRenderer->resolve('JSON_FIX', $run->project_id, ['invalid_json' => $invalidJson]);
        $modelConfig = $resolved['model_config'];
        if (!$modelConfig) {
            \Illuminate\Support\Facades\Log::warning('AgentRunner: JSON_FIX retry skipped (no model config)');
            return null;
        }
        $response = $this->openRouterClient->chat($apiKey, $modelConfig->model_name, [
            ['role' => 'user', 'content' => $resolved['prompt_text']],
        ], ['max_tokens' => $modelConfig->max_tokens]);
        $fixed = $this->jsonGuard->decode($response['content'] ?? '');
        if ($fixed === null) {
            \Illuminate\Support\Facades\Log::warning('AgentRunner: JSON_FIX retry still produced invalid JSON');
            return null;
        }
        return [
            'settings' => $fixed['settings'] ?? [],
            'blocks' => $fixed['blocks'] ?? [],
            'block_order' => $fixed['block_order'] ?? [],
        ];
    }

    private function createStep(AgentRun $run, string $stepKey, string $status): AgentStep
    {
        return AgentStep::create([
            'agent_run_id' => $run->id,
            'step_key' => $stepKey,
            'status' => $status,
        ]);
    }

    private function getApiKey(int $projectId): ?string
    {
        $secret = ProjectSecret::where('project_id', $projectId)->where('key', 'openrouter_api_key')->first();
        $key = $secret ? $secret->getDecryptedValue() : null;
        if ($key !== null && $key !== '') {
            return $key;
        }
        return Setting::getValue('openrouter_api_key');
    }
}
