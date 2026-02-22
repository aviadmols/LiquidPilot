<?php

namespace App\Jobs;

use App\Domain\Agent\AIProgressLogger;
use App\Domain\Media\MediaPlanner;
use App\Models\AgentRun;
use App\Models\AgentStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MediaPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(MediaPlanner $planner): void
    {
        Log::info('MediaPlanJob started', ['agent_run_id' => $this->agentRunId]);

        $run = AgentRun::findOrFail($this->agentRunId);
        AIProgressLogger::forRun($run->id)->log('media_plan', 'info', 'MediaPlan job started', []);

        try {
            $composeStep = AgentStep::where('agent_run_id', $run->id)->where('step_key', 'compose')->latest('id')->first();
            $indexJson = $composeStep?->output_json ?? [];
            $extractedPath = $run->themeRevision->extracted_path;
            if (!$extractedPath || !is_dir($extractedPath)) {
                GenerateMediaJob::dispatch($this->agentRunId);
                return;
            }
            $planner->plan($indexJson, $extractedPath);
            GenerateMediaJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            Log::error('MediaPlanJob failed', [
                'agent_run_id' => $this->agentRunId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            AIProgressLogger::forRun($run->id)->logError('media_plan', $e->getMessage(), [
                'exception' => get_class($e),
            ]);
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }
}
