<?php

namespace App\Domain\Agent;

use App\Models\AIActionLog;
use App\Models\AgentRun;

/**
 * Writes live progress entries to AIActionLogs for a given agent run.
 * Used before/after every AI call, chunk progress, validation, retries.
 * Never logs secrets (e.g. API keys).
 *
 * Side effects: Inserts into ai_action_logs; may update agent_runs.progress.
 */
class AIProgressLogger
{
    public function __construct(
        private readonly int $agentRunId
    ) {}

    public static function forRun(int $agentRunId): self
    {
        return new self($agentRunId);
    }

    public function logStart(string $stepKey, string $message, array $context = []): void
    {
        $this->log($stepKey, 'info', $message, $context);
    }

    public function logEnd(string $stepKey, string $message, array $context = []): void
    {
        $this->log($stepKey, 'info', $message, $context);
    }

    public function logChunk(string $stepKey, int $chunkIndex, int $total, string $message = ''): void
    {
        $msg = $message ?: "Chunk {$chunkIndex}/{$total}";
        $this->log($stepKey, 'info', $msg, ['chunk' => $chunkIndex, 'total' => $total]);
    }

    public function logValidation(string $stepKey, bool $passed, ?string $reason = null): void
    {
        $message = $passed ? 'Validation passed' : 'Validation failed: ' . ($reason ?? 'unknown');
        $this->log($stepKey, $passed ? 'info' : 'warning', $message, ['passed' => $passed]);
    }

    public function logRetry(string $stepKey, int $attempt, int $max, ?string $reason = null): void
    {
        $msg = ($reason ? $reason . '. ' : '') . "Retry {$attempt}/{$max}";
        $this->log($stepKey, 'info', $msg, ['attempt' => $attempt, 'max' => $max]);
    }

    public function logError(string $stepKey, string $message, array $context = []): void
    {
        $this->log($stepKey, 'error', $message, $context);
    }

    public function log(string $stepKey, string $level, string $message, array $context = []): void
    {
        AIActionLog::create([
            'agent_run_id' => $this->agentRunId,
            'step_key' => $stepKey,
            'level' => $level,
            'message' => $message,
            'context_json' => $context ?: null,
        ]);
    }

    public function updateProgress(int $progress): void
    {
        AgentRun::where('id', $this->agentRunId)->update(['progress' => min(100, max(0, $progress))]);
    }
}
