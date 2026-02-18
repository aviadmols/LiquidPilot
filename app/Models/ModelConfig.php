<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelConfig extends Model
{
    protected $fillable = [
        'project_id', 'provider', 'model_name', 'temperature', 'max_tokens', 'top_p', 'json_mode', 'is_active',
    ];

    protected $casts = [
        'json_mode' => 'boolean',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return HasMany<PromptBinding> */
    public function promptBindings(): HasMany
    {
        return $this->hasMany(PromptBinding::class);
    }
}
