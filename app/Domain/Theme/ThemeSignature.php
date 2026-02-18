<?php

namespace App\Domain\Theme;

use Illuminate\Support\Facades\File;

/**
 * Computes a SHA-256 signature from relevant theme files (sections, templates, config, locales)
 * for cache invalidation. Same content => same signature.
 *
 * Inputs: Path to extracted theme root.
 * Outputs: Hex signature string.
 * Side effects: Reads files only.
 */
class ThemeSignature
{
    public function compute(string $extractedPath): string
    {
        $extractedPath = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $extractedPath), DIRECTORY_SEPARATOR);
        $globs = config('theme.signature_globs', [
            'sections/*.liquid',
            'templates/*.json',
            'config/*.json',
            'locales/*.json',
        ]);

        $content = '';
        foreach ($globs as $glob) {
            $fullGlob = $extractedPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $glob);
            $files = glob($fullGlob);
            if ($files === false) {
                continue;
            }
            sort($files);
            foreach ($files as $file) {
                if (is_file($file)) {
                    $content .= $file . "\n" . File::get($file) . "\n";
                }
            }
        }

        return hash('sha256', $content);
    }
}
