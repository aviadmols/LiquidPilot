<?php

namespace App\Domain\Theme;

use Illuminate\Support\Facades\File;

/**
 * Writes .zyg catalog cache files inside extracted theme: catalog.json, catalog.summary.json,
 * signature.txt, catalog_version.txt.
 *
 * Inputs: Extracted theme path, full catalog array, signature, optional summary.
 * Outputs: none.
 * Side effects: Writes files to theme .zyg directory.
 */
class ThemeCatalogWriter
{
    public function write(string $extractedPath, array $catalog, string $signatureSha256, ?array $summary = null): void
    {
        $extractedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $extractedPath), DIRECTORY_SEPARATOR);
        $zygDir = $extractedPath . DIRECTORY_SEPARATOR . config('theme.zyg_dir', '.zyg');
        if (!is_dir($zygDir)) {
            mkdir($zygDir, 0755, true);
        }

        $catalogFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_catalog_filename', 'catalog.json');
        File::put($catalogFile, json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $summaryFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_catalog_summary_filename', 'catalog.summary.json');
        $summaryData = $summary ?? ($catalog['summary'] ?? $catalog['sections'] ?? []);
        File::put($summaryFile, json_encode($summaryData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $sigFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_signature_filename', 'signature.txt');
        File::put($sigFile, $signatureSha256);

        $versionFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_catalog_version_filename', 'catalog_version.txt');
        File::put($versionFile, config('theme.catalog_version', '1'));
    }
}
