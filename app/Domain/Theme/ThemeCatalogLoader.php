<?php

namespace App\Domain\Theme;

use Illuminate\Support\Facades\File;

/**
 * Loads cached catalog from .zyg/catalog.json if signature matches.
 * Returns catalog data array or null if cache miss or invalid.
 *
 * Inputs: Extracted theme path, current signature_sha256.
 * Outputs: array|null (decoded catalog.json).
 * Side effects: Reads files only.
 */
class ThemeCatalogLoader
{
    public function loadIfValid(string $extractedPath, string $signatureSha256): ?array
    {
        $zygDir = rtrim($extractedPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
            . config('theme.zyg_dir', '.zyg');
        $sigFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_signature_filename', 'signature.txt');
        $catalogFile = $zygDir . DIRECTORY_SEPARATOR . config('theme.zyg_catalog_filename', 'catalog.json');

        if (!is_file($sigFile) || !is_file($catalogFile)) {
            return null;
        }
        $cachedSig = trim(File::get($sigFile));
        if ($cachedSig !== $signatureSha256) {
            return null;
        }
        $json = File::get($catalogFile);
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }
}
