<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptTemplate extends Model
{
    protected $fillable = ['key', 'template_text', 'input_schema_json', 'output_schema_json', 'version', 'is_active', 'default_model_name'];

    protected $casts = [
        'input_schema_json' => 'array',
        'output_schema_json' => 'array',
        'is_active' => 'boolean',
    ];

    /** @return HasMany<PromptBinding> */
    public function promptBindings(): HasMany
    {
        return $this->hasMany(PromptBinding::class);
    }
}
