<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeCatalog extends Model
{
    protected $fillable = ['theme_revision_id', 'catalog_json', 'version'];

    protected $casts = [
        'catalog_json' => 'array',
    ];

    /** @return BelongsTo<ThemeRevision, $this> */
    public function themeRevision(): BelongsTo
    {
        return $this->belongsTo(ThemeRevision::class);
    }
}
