<?php

namespace App\Domain\Theme;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Accepts uploaded theme ZIP, validates size, stores under storage/app/themes/{revisionId}/.
 * Does not execute or parse theme code.
 *
 * Inputs: UploadedFile, revision id for path.
 * Outputs: Stored path (relative to disk).
 * Side effects: Writes file to storage.
 */
class ThemeZipService
{
    public function __construct(
        private readonly int $maxSizeBytes
    ) {}

    public static function fromConfig(): self
    {
        return new self((int) config('theme.zip_max_size_bytes', 100 * 1024 * 1024));
    }

    /**
     * Store uploaded ZIP and return path relative to storage disk.
     *
     * @throws \InvalidArgumentException if size exceeds limit or not a zip
     */
    public function store(UploadedFile $file, int $revisionId): string
    {
        if (strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw new \InvalidArgumentException('File must be a ZIP archive.');
        }
        if ($file->getSize() > $this->maxSizeBytes) {
            throw new \InvalidArgumentException('ZIP size exceeds maximum allowed.');
        }

        $dir = 'themes/' . $revisionId;
        $path = $file->storeAs($dir, 'theme.zip', ['disk' => 'local']);

        return $path;
    }

    /**
     * Store a file from an existing path (e.g. Livewire temp) to themes/{revisionId}/theme.zip.
     * Side effects: Writes file to storage.
     */
    public function storeFromPath(string $sourcePath, int $revisionId, string $originalName = 'theme.zip'): string
    {
        $dir = 'themes/' . $revisionId;
        $relativePath = $dir . '/theme.zip';
        Storage::disk('local')->put($relativePath, file_get_contents($sourcePath));
        return $relativePath;
    }

    public function fullPath(string $relativePath): string
    {
        return Storage::disk('local')->path($relativePath);
    }
}
