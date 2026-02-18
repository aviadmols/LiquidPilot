<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = ['name', 'locale', 'status'];

    /** @return HasMany<BrandKit> */
    public function brandKits(): HasMany
    {
        return $this->hasMany(BrandKit::class);
    }

    /** @return HasMany<ThemeRevision> */
    public function themeRevisions(): HasMany
    {
        return $this->hasMany(ThemeRevision::class, 'project_id');
    }

    /** @return HasMany<ModelConfig> */
    public function modelConfigs(): HasMany
    {
        return $this->hasMany(ModelConfig::class, 'project_id');
    }

    /** @return HasMany<PromptBinding> */
    public function promptBindings(): HasMany
    {
        return $this->hasMany(PromptBinding::class, 'project_id');
    }

    /** @return HasMany<ProjectSecret> */
    public function secrets(): HasMany
    {
        return $this->hasMany(ProjectSecret::class, 'project_id');
    }

    /** @return HasMany<AgentRun> */
    public function agentRuns(): HasMany
    {
        return $this->hasMany(AgentRun::class, 'project_id');
    }
}
