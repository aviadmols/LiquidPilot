<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AIActionLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ai_action_logs';

    protected $fillable = ['agent_run_id', 'step_key', 'level', 'message', 'context_json'];

    protected $casts = [
        'context_json' => 'array',
    ];

    /** @return BelongsTo<AgentRun, $this> */
    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }
}
