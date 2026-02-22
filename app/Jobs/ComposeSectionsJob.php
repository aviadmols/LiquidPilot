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

class ComposeSectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        Log::info('ComposeSectionsJob started', ['agent_run_id' => $this->agentRunId]);

        $run = AgentRun::findOrFail($this->agentRunId);
        $run->refresh();
        if (in_array($run->status, [AgentRun::STATUS_CANCELLED, AgentRun::STATUS_FAILED], true)) {
            return;
        }
        AIProgressLogger::forRun($run->id)->log('compose', 'info', 'Compose step job started', []);

        try {
            $planStep = AgentStep::where('agent_run_id', $run->id)->where('step_key', 'plan')->latest('id')->first();
            $plan = $planStep?->output_json ?? ['sections' => []];
            if ($run->mode === AgentRun::MODE_TEST && $run->selected_section_handle) {
                $plan = ['sections' => [$run->selected_section_handle]];
            }
            $runner->runCompose($run, $plan);
            MediaPlanJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            Log::error('ComposeSectionsJob failed', [
                'agent_run_id' => $this->agentRunId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            AIProgressLogger::forRun($run->id)->logError('compose', $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }
}
