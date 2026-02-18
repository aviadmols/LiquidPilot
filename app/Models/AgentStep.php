<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentStep extends Model
{
    protected $fillable = [
        'agent_run_id', 'step_key', 'status', 'input_json', 'output_json',
        'logs_text', 'tokens_in', 'tokens_out', 'cost_estimate',
    ];

    protected $casts = [
        'input_json' => 'array',
        'output_json' => 'array',
    ];

    /** @return BelongsTo<AgentRun, $this> */
    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }
}
