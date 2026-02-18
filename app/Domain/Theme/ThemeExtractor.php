<?php

namespace App\Domain\Theme;

use ZipArchive;

/**
 * Extracts theme ZIP to a unique directory with zip-slip prevention and allowed extensions only.
 * Uses PHP ZipArchive. Does not execute any theme code.
 *
 * Inputs: Path to ZIP file, path to extraction base directory.
 * Outputs: Path to extracted root (first folder or files at root).
 * Side effects: Writes files to filesystem.
 */
class ThemeExtractor
{
    /** @var list<string> */
    private array $allowedExtensions;

    public function __construct(?array $allowedExtensions = null)
    {
        $this->allowedExtensions = $allowedExtensions ?? array_map(
            'strtolower',
            config('theme.allowed_extensions', ['liquid', 'json', 'css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])
        );
    }

    /**
     * Extract ZIP to destDir. Rejects any path that escapes destDir (zip-slip).
     * Only extracts files whose extension is in allowed list.
     *
     * @return string Path to extracted theme root (directory containing sections/, templates/, etc.)
     */
    public function extract(string $zipPath, string $destDir): string
    {
        $destDir = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destDir), DIRECTORY_SEPARATOR);
        $realDest = realpath($destDir) ?: $destDir;

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new \RuntimeException('Cannot open ZIP: ' . $zipPath);
        }

        $rootEntries = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $rawName = $zip->getNameIndex($i);
            if ($rawName === false) {
                continue;
            }
            $entry = $this->normalizeEntryName($rawName);
            if ($entry === '' || str_contains($entry, '..')) {
                continue;
            }
            $targetPath = $destDir . DIRECTORY_SEPARATOR . $entry;
            if ($this->wouldEscape($targetPath, $realDest)) {
                continue;
            }
            if (!$this->isAllowed($entry)) {
                continue;
            }
            $zip->extractTo($destDir, [$rawName]);
            $parts = explode('/', str_replace('\\', '/', $entry));
            $rootEntries[] = $parts[0];
        }
        $zip->close();

        $uniqueRoots = array_unique(array_filter($rootEntries));
        if (count($uniqueRoots) === 1 && is_dir($destDir . DIRECTORY_SEPARATOR . $uniqueRoots[0])) {
            return $destDir . DIRECTORY_SEPARATOR . $uniqueRoots[0];
        }
        return $destDir;
    }

    private function normalizeEntryName(string $entry): string
    {
        $entry = str_replace('\\', '/', $entry);
        $entry = preg_replace('#/+#', '/', $entry);
        return trim($entry, '/');
    }

    private function wouldEscape(string $targetPath, string $baseDir): bool
    {
        $base = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $baseDir);
        $realBase = realpath($base) ?: $base;
        $full = $targetPath;
        $realFull = realpath(dirname($full)) !== false
            ? dirname($full) . DIRECTORY_SEPARATOR . basename($full)
            : $full;
        $resolved = realpath($realFull) ?: $realFull;
        return $realBase !== '' && strpos($resolved, $realBase) !== 0;
    }

    private function isAllowed(string $entry): bool
    {
        $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
        return in_array($ext, $this->allowedExtensions, true);
    }
}
