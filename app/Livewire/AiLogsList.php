<?php

namespace App\Livewire;

use App\Models\AIActionLog;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Displays AI action logs for an agent run with polling for near-real-time updates.
 */
class AiLogsList extends Component
{
    public int $agentRunId;

    public function mount(int $agentRunId): void
    {
        $this->agentRunId = $agentRunId;
    }

    public function getLogsProperty(): \Illuminate\Database\Eloquent\Collection
    {
        return AIActionLog::where('agent_run_id', $this->agentRunId)
            ->orderBy('id')
            ->get();
    }

    public function render()
    {
        return view('livewire.ai-logs-list');
    }
}
