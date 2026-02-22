<?php

namespace App\Jobs;

use App\Domain\Agent\AIProgressLogger;
use App\Domain\Agent\AgentRunner;
use App\Models\AgentRun;
use App\Models\AgentStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SummarizeCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        Log::info('SummarizeCatalogJob started', ['agent_run_id' => $this->agentRunId]);

        $run = AgentRun::findOrFail($this->agentRunId);
        $run->refresh();
        if (in_array($run->status, [AgentRun::STATUS_CANCELLED, AgentRun::STATUS_FAILED], true)) {
            return;
        }
        AIProgressLogger::forRun($run->id)->log('summary', 'info', 'Job started', []);
        $run->update(['status' => AgentRun::STATUS_RUNNING, 'started_at' => now()]);
        AIProgressLogger::forRun($run->id)->updateProgress(2);

        Log::info('Agent run status set to RUNNING', ['agent_run_id' => $this->agentRunId]);

        try {
            if ($run->mode === AgentRun::MODE_TEST && $run->selected_section_handle) {
                AIProgressLogger::forRun($run->id)->log('summary', 'info', 'Test run: skipping full catalog summary, using selected section only', []);
                AgentStep::create([
                    'agent_run_id' => $run->id,
                    'step_key' => 'summary',
                    'status' => 'completed',
                    'output_json' => ['summary' => [], 'section_handles' => [$run->selected_section_handle]],
                ]);
                AIProgressLogger::forRun($run->id)->updateProgress(10);
                PlanHomepageJob::dispatch($this->agentRunId);
                return;
            }

            Log::info('runSummarize() starting', ['agent_run_id' => $this->agentRunId]);
            $summary = $runner->runSummarize($run);
            Log::info('runSummarize() completed', ['agent_run_id' => $this->agentRunId]);
            PlanHomepageJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            Log::error('SummarizeCatalogJob failed', [
                'agent_run_id' => $this->agentRunId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            AIProgressLogger::forRun($run->id)->logError('summary', $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            // Do not rethrow configuration errors so the request returns 200 and the user sees the run as Failed with a clear message instead of a 500 page
            $isConfigError = $e instanceof \RuntimeException && str_contains($e->getMessage(), 'Missing OpenRouter API key');
            if (! $isConfigError) {
                throw $e;
            }
        }
    }
}
