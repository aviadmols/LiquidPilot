<?php

namespace App\Domain\Theme;

use App\Models\ThemeRevision;

/**
 * Builds a catalog summary report from a theme revision (sections, blocks, settings/elements).
 * Used for the View page and optional download.
 */
class ThemeCatalogReportBuilder
{
    /**
     * Build structured report array from revision's catalog.
     *
     * @return array{sections: array<int, array{name: string, handle: string, enabled_on: mixed, max_blocks: mixed, settings_count: int, blocks_count: int, block_types: array<int, string>, settings: array, blocks: array}>}
     */
    public function build(ThemeRevision $revision): array
    {
        $revision->load('themeCatalog');
        $catalog = $revision->themeCatalog?->catalog_json ?? [];
        $sections = $catalog['sections'] ?? [];
        $reportSections = [];

        foreach ($sections as $section) {
            $settings = $section['settings'] ?? [];
            $blocks = $section['blocks'] ?? [];
            $blockTypes = array_values(array_unique(array_filter(array_map(function (array $b) {
                $t = $b['type'] ?? null;
                return is_string($t) ? $t : null;
            }, $blocks))));

            $settingsSummary = [];
            foreach ($settings as $s) {
                $id = $s['id'] ?? null;
                $type = $s['type'] ?? 'text';
                $label = $s['label'] ?? '';
                $optionsSummary = null;
                if (isset($s['options']) && is_array($s['options'])) {
                    $count = count($s['options']);
                    $optionsSummary = $count . ' option(s)';
                }
                $settingsSummary[] = array_filter([
                    'id' => $id,
                    'type' => $type,
                    'label' => $label,
                    'options_summary' => $optionsSummary,
                ]);
            }

            $blocksSummary = [];
            foreach ($blocks as $b) {
                $blockSettings = $b['settings'] ?? [];
                $blocksSummary[] = [
                    'type' => $b['type'] ?? 'text',
                    'name' => $b['name'] ?? '',
                    'settings_count' => count($blockSettings),
                ];
            }

            $reportSections[] = [
                'name' => $section['name'] ?? $section['handle'] ?? '',
                'handle' => $section['handle'] ?? '',
                'enabled_on' => $section['enabled_on'] ?? null,
                'max_blocks' => $section['max_blocks'] ?? null,
                'settings_count' => count($settings),
                'blocks_count' => count($blocks),
                'block_types' => $blockTypes,
                'settings' => $settingsSummary,
                'blocks' => $blocksSummary,
            ];
        }

        return ['sections' => $reportSections];
    }

    /**
     * Export report as Markdown text for download.
     */
    public function buildMarkdown(ThemeRevision $revision): string
    {
        $report = $this->build($revision);
        $lines = [
            '# Theme catalog summary',
            '',
            '**Revision:** ' . $revision->original_filename,
            '**Scanned at:** ' . ($revision->scanned_at?->toDateTimeString() ?? '-'),
            '',
        ];

        foreach ($report['sections'] as $i => $sec) {
            $lines[] = '## ' . ($i + 1) . '. ' . $sec['name'] . ' (`' . $sec['handle'] . '`)';
            $lines[] = '';
            $lines[] = '- **Enabled on:** ' . (is_array($sec['enabled_on']) ? implode(', ', $sec['enabled_on']) : (string) $sec['enabled_on']);
            $lines[] = '- **Max blocks:** ' . ($sec['max_blocks'] ?? '-');
            $lines[] = '- **Settings:** ' . $sec['settings_count'];
            $lines[] = '- **Blocks:** ' . $sec['blocks_count'];
            $lines[] = '- **Block types:** ' . implode(', ', $sec['block_types'] ?: ['-']);
            $lines[] = '';
            $lines[] = '### Settings / design elements';
            $lines[] = '';
            foreach ($sec['settings'] as $s) {
                $opt = isset($s['options_summary']) ? ' — ' . $s['options_summary'] : '';
                $lines[] = '- `' . ($s['id'] ?? '') . '` (' . ($s['type'] ?? '') . '): ' . ($s['label'] ?? '') . $opt;
            }
            $lines[] = '';
            $lines[] = '### Blocks';
            $lines[] = '';
            foreach ($sec['blocks'] as $b) {
                $lines[] = '- **' . ($b['type'] ?? '') . '** — ' . ($b['name'] ?? '') . ' (' . $b['settings_count'] . ' settings)';
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
