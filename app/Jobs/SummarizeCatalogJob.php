<?php

namespace App\Jobs;

use App\Domain\Agent\AgentRunner;
use App\Models\AgentRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SummarizeCatalogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $agentRunId) {}

    public function handle(AgentRunner $runner): void
    {
        $run = AgentRun::findOrFail($this->agentRunId);
        $run->update(['status' => AgentRun::STATUS_RUNNING, 'started_at' => now()]);
        try {
            $summary = $runner->runSummarize($run);
            PlanHomepageJob::dispatch($this->agentRunId);
        } catch (\Throwable $e) {
            $run->update(['status' => AgentRun::STATUS_FAILED, 'error' => $e->getMessage(), 'finished_at' => now()]);
            throw $e;
        }
    }
}
