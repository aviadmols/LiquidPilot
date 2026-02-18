<?php

namespace App\Domain\Theme;

use Illuminate\Support\Facades\File;

/**
 * Scans sections/*.liquid in extracted theme, parses {% schema %} blocks,
 * normalizes settings/blocks/presets and builds full catalog structure.
 *
 * Inputs: Path to extracted theme root.
 * Outputs: array with 'sections' (list of normalized section data), 'summary' (compact).
 * Side effects: Reads files only.
 */
class ThemeScanner
{
    public function scan(string $extractedPath): array
    {
        $extractedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $extractedPath), DIRECTORY_SEPARATOR);
        $sectionsDir = $extractedPath . DIRECTORY_SEPARATOR . 'sections';
        if (!is_dir($sectionsDir)) {
            return ['sections' => [], 'summary' => ['handles' => [], 'section_count' => 0]];
        }

        $files = glob($sectionsDir . DIRECTORY_SEPARATOR . '*.liquid');
        $sections = [];
        $handles = [];
        foreach ($files ?: [] as $file) {
            $handle = pathinfo($file, PATHINFO_FILENAME);
            $content = File::get($file);
            $schema = $this->extractSchema($content);
            if ($schema === null) {
                continue;
            }
            $normalized = $this->normalizeSchema($schema, $handle);
            $sections[] = $normalized;
            $handles[] = $handle;
        }

        return [
            'sections' => $sections,
            'summary' => [
                'handles' => $handles,
                'section_count' => count($sections),
            ],
        ];
    }

    private function extractSchema(string $content): ?array
    {
        if (!preg_match('/\{%\s*schema\s*%\}(.*)\{%\s*endschema\s*%\}/s', $content, $m)) {
            return null;
        }
        $json = trim($m[1]);
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeSchema(array $schema, string $handle): array
    {
        $out = [
            'name' => $schema['name'] ?? $handle,
            'handle' => $handle,
            'settings' => $this->normalizeSettings($schema['settings'] ?? []),
            'blocks' => $this->normalizeBlocks($schema['blocks'] ?? []),
            'presets' => $schema['presets'] ?? [],
            'max_blocks' => $schema['max_blocks'] ?? null,
            'enabled_on' => $schema['enabled_on'] ?? null,
            'templates' => $schema['templates'] ?? null,
        ];
        return $out;
    }

    private function normalizeSettings(array $settings): array
    {
        $out = [];
        $known = ['id', 'type', 'label', 'default', 'options', 'min', 'max', 'step'];
        foreach ($settings as $s) {
            $normalized = [
                'id' => $s['id'] ?? null,
                'type' => $s['type'] ?? 'text',
                'label' => $s['label'] ?? '',
                'default' => $s['default'] ?? null,
                'options' => $s['options'] ?? null,
                'min' => $s['min'] ?? null,
                'max' => $s['max'] ?? null,
                'step' => $s['step'] ?? null,
            ];
            foreach ($s as $key => $value) {
                if (!in_array($key, $known, true)) {
                    $normalized[$key] = $value;
                }
            }
            $out[] = $normalized;
        }
        return $out;
    }

    private function normalizeBlocks(array $blocks): array
    {
        $out = [];
        $known = ['type', 'name', 'settings'];
        foreach ($blocks as $b) {
            $normalized = [
                'type' => $b['type'] ?? 'text',
                'name' => $b['name'] ?? '',
                'settings' => $this->normalizeSettings($b['settings'] ?? []),
            ];
            foreach ($b as $key => $value) {
                if ($key === 'settings') {
                    continue;
                }
                if (!in_array($key, $known, true)) {
                    $normalized[$key] = $value;
                }
            }
            $out[] = $normalized;
        }
        return $out;
    }
}
