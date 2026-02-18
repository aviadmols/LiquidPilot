<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandKit extends Model
{
    protected $fillable = [
        'project_id', 'brand_name', 'brand_type', 'industry', 'tone_of_voice', 'language',
        'colors_json', 'typography_json', 'imagery_style_json', 'audience_json', 'product_info_json',
    ];

    protected $casts = [
        'colors_json' => 'array',
        'typography_json' => 'array',
        'imagery_style_json' => 'array',
        'audience_json' => 'array',
        'product_info_json' => 'array',
    ];

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
