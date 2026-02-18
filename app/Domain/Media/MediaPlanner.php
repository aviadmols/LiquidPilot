<?php

namespace App\Domain\Media;

use Illuminate\Support\Facades\File;

/**
 * From composed sections JSON, collects image/settings that need assets.
 * Outputs list of required assets (purpose, dimensions) and writes .zyg/media_manifest.json
 * mapping asset paths to section/setting ids for later Shopify linking.
 *
 * Inputs: Composed index structure (sections + settings), extracted theme path.
 * Outputs: list of ['purpose' => string, 'width' => int, 'height' => int, 'setting_id' => string, 'section_id' => string].
 * Side effects: Writes .zyg/media_manifest.json.
 */
class MediaPlanner
{
    private const IMAGE_TYPES = ['image_picker', 'image', 'url'];

    /**
     * @return list<array{purpose: string, width: int, height: int, setting_id: string, section_id: string}> 
     */
    public function plan(array $indexJson, string $extractedPath): array
    {
        $required = [];
        $sections = $indexJson['sections'] ?? [];
        $order = $indexJson['order'] ?? array_keys($sections);
        foreach ($order as $sectionId) {
            $section = $sections[$sectionId] ?? null;
            if (!$section || !is_array($section)) {
                continue;
            }
            $settings = $section['settings'] ?? [];
            foreach ($settings as $settingId => $value) {
                if (is_string($value) && (str_starts_with($value, 'http') || strlen($value) > 0)) {
                    $required[] = [
                        'purpose' => $sectionId . '_' . $settingId,
                        'width' => 1200,
                        'height' => 800,
                        'setting_id' => $settingId,
                        'section_id' => $sectionId,
                    ];
                }
            }
        }
        $manifest = array_map(fn ($r) => [
            'purpose' => $r['purpose'],
            'width' => $r['width'],
            'height' => $r['height'],
            'setting_id' => $r['setting_id'],
            'section_id' => $r['section_id'],
        ], $required);
        $this->writeManifest($extractedPath, $manifest);
        return $required;
    }

    private function writeManifest(string $extractedPath, array $manifest): void
    {
        $zygDir = rtrim($extractedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . config('theme.zyg_dir', '.zyg');
        if (!is_dir($zygDir)) {
            mkdir($zygDir, 0755, true);
        }
        $file = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_media_manifest_filename', 'media_manifest.json');
        File::put($file, json_encode(['assets' => $manifest], JSON_PRETTY_PRINT));
    }
}
