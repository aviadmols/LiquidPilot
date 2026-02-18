<?php

namespace App\Jobs;

use App\Domain\Agent\AgentRunner;
use App\Models\AgentRun;
use App\Models\AgentStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComposeSectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $planStep = AgentStep::where('agent_run_id', $run->id)->where('step_key', 'plan')->latest('id')->first();
        $plan = $planStep?->output_json ?? ['sections' => []];
        if ($run->mode === AgentRun::MODE_TEST && $run->selected_section_handle) {
            $plan = ['sections' => [$run->selected_section_handle]];
        }
        $runner->runCompose($run, $plan);
        MediaPlanJob::dispatch($this->agentRunId);
    }
}
