<?php

namespace App\Jobs;

use App\Domain\Agent\AgentRunner;
use App\Models\AgentRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class PlanHomepageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $summaryStep = \App\Models\AgentStep::where('agent_run_id', $run->id)->where('step_key', 'summary')->first();
        $summary = $summaryStep?->output_json ?? [];
        $runner->runPlan($run, $summary);
        ComposeSectionsJob::dispatch($this->agentRunId);
    }
}
