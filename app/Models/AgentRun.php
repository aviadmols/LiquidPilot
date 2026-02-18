<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AgentRun extends Model
{
    public const MODE_FULL = 'full';
    public const MODE_TEST = 'test';
    public const OUTPUT_FULL_ZIP = 'full_zip';
    public const OUTPUT_MEDIA_AND_JSON = 'media_and_json';
    public const OUTPUT_BOTH = 'both';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'project_id', 'theme_revision_id', 'mode', 'selected_section_handle', 'output_format', 'creative_brief',
        'status', 'progress', 'started_at', 'finished_at', 'error',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<ThemeRevision, $this> */
    public function themeRevision(): BelongsTo
    {
        return $this->belongsTo(ThemeRevision::class);
    }

    /** @return HasMany<AgentStep> */
    public function agentSteps(): HasMany
    {
        return $this->hasMany(AgentStep::class);
    }

    /** @return HasMany<AIActionLog> */
    public function aiActionLogs(): HasMany
    {
        return $this->hasMany(AIActionLog::class);
    }

    /** @return HasMany<MediaAsset> */
    public function mediaAssets(): HasMany
    {
        return $this->hasMany(MediaAsset::class);
    }

    /** @return HasOne<Export> */
    public function export(): HasOne
    {
        return $this->hasOne(Export::class);
    }
}
