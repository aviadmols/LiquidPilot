<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStep extends Model
{
    protected $fillable = [
        'agent_run_id', 'step_key', 'status', 'input_json', 'output_json',
        'logs_text', 'tokens_in', 'tokens_out', 'cost_estimate',
    ];

    protected $casts = [
        'input_json' => 'array',
        'output_json' => 'array',
    ];

    /** @return BelongsTo<AgentRun, $this> */
    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }

    /** Human-readable label for step_key. */
    public function getStepLabel(): string
    {
        return match ($this->step_key) {
            'summary' => 'Catalog summary',
            'plan' => 'Homepage plan',
            'compose' => 'Compose sections',
            default => $this->step_key,
        };
    }

    /**
     * Bullet points describing what the agent chose in this step (for UI).
     * @return array<int, string>
     */
    public function getChoicesBullets(): array
    {
        $out = $this->output_json ?? [];
        return match ($this->step_key) {
            'summary' => $this->bulletsFromSummary($out),
            'plan' => $this->bulletsFromPlan($out),
            'compose' => $this->bulletsFromCompose($out),
            default => [json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)],
        };
    }

    /** @return array<int, string> */
    private function bulletsFromSummary(array $out): array
    {
        $summary = $out['summary'] ?? [];
        if (!is_array($summary)) {
            return ['No summary found'];
        }
        $lines = [];
        foreach ($summary as $i => $item) {
            if (is_string($item)) {
                $lines[] = $item;
            } elseif (is_array($item)) {
                $lines[] = implode(' – ', array_filter($item));
            } else {
                $lines[] = (string) $item;
            }
        }
        return $lines ?: ['No items'];
    }

    /** @return array<int, string> */
    private function bulletsFromPlan(array $out): array
    {
        $sections = $out['sections'] ?? [];
        if (!is_array($sections)) {
            return ['No sections found'];
        }
        $lines = [];
        foreach ($sections as $s) {
            if (is_string($s)) {
                $lines[] = $s;
            } elseif (is_array($s) && isset($s['handle'])) {
                $lines[] = $s['handle'];
            } elseif (is_array($s) && isset($s['type'])) {
                $lines[] = $s['type'];
            } else {
                $lines[] = is_scalar($s) ? (string) $s : json_encode($s);
            }
        }
        return $lines ?: ['No sections'];
    }

    /** @return array<int, string> */
    private function bulletsFromCompose(array $out): array
    {
        $sections = $out['sections'] ?? [];
        $order = $out['order'] ?? array_keys($sections);
        if (!is_array($sections)) {
            return ['No sections found'];
        }
        $lines = [];
        foreach ($order as $id) {
            $sec = $sections[$id] ?? null;
            if (!$sec || !is_array($sec)) {
                $lines[] = "{$id}: -";
                continue;
            }
            $type = $sec['type'] ?? $id;
            $blocks = $sec['blocks'] ?? [];
            $n = is_array($blocks) ? count($blocks) : 0;
            $lines[] = "{$id}: {$type} ({$n} blocks)";
        }
        return $lines ?: ['No sections'];
    }
}
