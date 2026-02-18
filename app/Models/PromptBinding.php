<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromptBinding extends Model
{
    protected $fillable = ['project_id', 'prompt_template_id', 'model_config_id'];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<PromptTemplate, $this> */
    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class);
    }

    /** @return BelongsTo<ModelConfig, $this> */
    public function modelConfig(): BelongsTo
    {
        return $this->belongsTo(ModelConfig::class);
    }
}
