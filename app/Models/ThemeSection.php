<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeSection extends Model
{
    protected $fillable = ['theme_revision_id', 'handle', 'schema_json', 'presets_json', 'metadata_json'];

    protected $casts = [
        'schema_json' => 'array',
        'presets_json' => 'array',
        'metadata_json' => 'array',
    ];

    /** @return BelongsTo<ThemeRevision, $this> */
    public function themeRevision(): BelongsTo
    {
        return $this->belongsTo(ThemeRevision::class);
    }
}
