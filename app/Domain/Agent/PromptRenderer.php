<?php

namespace App\Domain\Agent;

use App\Models\ModelConfig;
use App\Models\PromptBinding;
use App\Models\PromptTemplate;
use App\Models\Project;

/**
 * Resolves prompt template by key, optional model config for project, and fills template with variables.
 * Does not call AI; only renders the final prompt text and returns model config for the client to use.
 *
 * Inputs: Template key, project id, variables (brand, catalog summary, section list, etc.).
 * Outputs: ['prompt_text' => string, 'model_config' => ModelConfig|null].
 * Side effects: DB read only.
 */
class PromptRenderer
{
    public function resolve(string $templateKey, int $projectId, array $variables = []): array
    {
        $template = PromptTemplate::where('key', $templateKey)->where('is_active', true)->first();
        if (!$template) {
            throw new \InvalidArgumentException("Unknown or inactive prompt template: {$templateKey}");
        }

        $binding = PromptBinding::where('project_id', $projectId)
            ->where('prompt_template_id', $template->id)
            ->with('modelConfig')
            ->first();
        $modelConfig = $binding?->modelConfig;

        if ($modelConfig === null && $template->default_model_name) {
            $modelConfig = ModelConfig::where('project_id', $projectId)
                ->where('is_active', true)
                ->where('model_name', $template->default_model_name)
                ->first();
        }

        $text = $this->fill($template->template_text, $variables);
        return [
            'prompt_text' => $text,
            'model_config' => $modelConfig,
        ];
    }

    /**
     * Simple placeholder replacement: {{ key }} with variables['key'].
     */
    private function fill(string $templateText, array $variables): string
    {
        $out = $templateText;
        foreach ($variables as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            }
            $out = str_replace('{{ ' . $key . ' }}', (string) $value, $out);
        }
        return $out;
    }
}
