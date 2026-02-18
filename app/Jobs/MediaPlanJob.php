<?php

namespace App\Jobs;

use App\Domain\Media\MediaPlanner;
use App\Models\AgentRun;
use App\Models\AgentStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MediaPlanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(MediaPlanner $planner): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $composeStep = AgentStep::where('agent_run_id', $run->id)->where('step_key', 'compose')->first();
        $indexJson = $composeStep?->output_json ?? [];
        $extractedPath = $run->themeRevision->extracted_path;
        if (!$extractedPath || !is_dir($extractedPath)) {
            GenerateMediaJob::dispatch($this->agentRunId);
            return;
        }
        $planner->plan($indexJson, $extractedPath);
        GenerateMediaJob::dispatch($this->agentRunId);
    }
}
