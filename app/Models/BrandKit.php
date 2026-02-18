<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandKit extends Model
{
    protected $fillable = [
        'project_id', 'brand_name', 'brand_type', 'industry', 'tone_of_voice', 'language',
        'colors_json', 'typography_json', 'imagery_style_json', 'audience_json', 'product_info_json',
        'logo_path', 'logo_design_notes',
    ];

    protected $casts = [
        'colors_json' => 'array',
        'typography_json' => 'array',
        'imagery_style_json' => 'array',
        'audience_json' => 'array',
        'product_info_json' => 'array',
    ];

    protected $appends = [
        'colors_primary',
        'colors_secondary',
        'colors_background',
        'imagery_keywords',
        'imagery_vibe',
        'typography_heading_font',
        'typography_body_font',
        'typography_notes',
    ];

    public function getColorsPrimaryAttribute(): ?string
    {
        $colors = $this->colors_json;
        return is_array($colors) ? ($colors['primary'] ?? null) : null;
    }

    public function getColorsSecondaryAttribute(): ?string
    {
        $colors = $this->colors_json;
        return is_array($colors) ? ($colors['secondary'] ?? null) : null;
    }

    public function getColorsBackgroundAttribute(): ?string
    {
        $colors = $this->colors_json;
        return is_array($colors) ? ($colors['background'] ?? null) : null;
    }

    public function getImageryKeywordsAttribute(): string
    {
        $json = $this->imagery_style_json;
        if (!is_array($json)) {
            return is_string($json) ? $json : '';
        }
        $kw = $json['keywords'] ?? null;
        if (is_array($kw)) {
            return implode(', ', $kw);
        }
        return is_string($kw) ? $kw : '';
    }

    public function getImageryVibeAttribute(): ?string
    {
        $json = $this->imagery_style_json;
        if (!is_array($json)) {
            return null;
        }
        $v = $json['vibe'] ?? $json['style'] ?? null;
        return is_string($v) ? $v : null;
    }

    public function getTypographyHeadingFontAttribute(): ?string
    {
        $json = $this->typography_json;
        return is_array($json) ? ($json['heading_font'] ?? null) : null;
    }

    public function getTypographyBodyFontAttribute(): ?string
    {
        $json = $this->typography_json;
        return is_array($json) ? ($json['body_font'] ?? null) : null;
    }

    public function getTypographyNotesAttribute(): ?string
    {
        $json = $this->typography_json;
        return is_array($json) ? ($json['notes'] ?? null) : null;
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
