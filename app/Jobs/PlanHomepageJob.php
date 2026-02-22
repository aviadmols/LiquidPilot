<?php

namespace App\Jobs;

use App\Domain\Agent\AIProgressLogger;
use App\Domain\Agent\AgentRunner;
use App\Models\AgentRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PlanHomepageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        Log::info('PlanHomepageJob started', ['agent_run_id' => $this->agentRunId]);

        $run = AgentRun::findOrFail($this->agentRunId);
        $run->refresh();
        if (in_array($run->status, [AgentRun::STATUS_CANCELLED, AgentRun::STATUS_FAILED], true)) {
            return;
        }
        AIProgressLogger::forRun($run->id)->log('plan', 'info', 'Plan step job started', []);

        try {
            if ($run->mode === AgentRun::MODE_TEST && $run->selected_section_handle) {
                AIProgressLogger::forRun($run->id)->log('plan', 'info', 'Test run: using selected section only (no AI plan)', []);
                \App\Models\AgentStep::create([
                    'agent_run_id' => $run->id,
                    'step_key' => 'plan',
                    'status' => 'completed',
                    'output_json' => ['sections' => [$run->selected_section_handle]],
                ]);
                ComposeSectionsJob::dispatch($this->agentRunId);
                return;
            }

            $summaryStep = \App\Models\AgentStep::where('agent_run_id', $run->id)->where('step_key', 'summary')->latest('id')->first();
            $summary = $summaryStep?->output_json ?? [];
            $runner->runPlan($run, $summary);
            ComposeSectionsJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            Log::error('PlanHomepageJob failed', [
                'agent_run_id' => $this->agentRunId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            AIProgressLogger::forRun($run->id)->logError('plan', $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }
}
