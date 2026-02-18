<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'agent_run_id', 'zip_path', 'template_json_path', 'media_archive_path', 'sha256', 'size',
    ];

    /** @return BelongsTo<AgentRun, $this> */
    public function agentRun(): BelongsTo
    {
        return $this->belongsTo(AgentRun::class);
    }
}
