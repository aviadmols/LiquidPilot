<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ThemeRevision extends Model
{
    protected $fillable = [
        'project_id', 'original_filename', 'zip_path', 'extracted_path',
        'signature_sha256', 'status', 'error', 'analysis_steps', 'catalog_path', 'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
        'analysis_steps' => 'array',
    ];

    public function appendAnalysisStep(string $step, string $message): void
    {
        $steps = $this->analysis_steps ?? [];
        $steps[] = [
            'step' => $step,
            'message' => $message,
            'at' => now()->toIso8601String(),
        ];
        $this->update(['analysis_steps' => $steps]);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasOne<ThemeCatalog> */
    public function themeCatalog(): HasOne
    {
        return $this->hasOne(ThemeCatalog::class);
    }

    /** @return HasMany<ThemeSection> */
    public function themeSections(): HasMany
    {
        return $this->hasMany(ThemeSection::class);
    }

    /** @return HasMany<AgentRun> */
    public function agentRuns(): HasMany
    {
        return $this->hasMany(AgentRun::class);
    }
}
