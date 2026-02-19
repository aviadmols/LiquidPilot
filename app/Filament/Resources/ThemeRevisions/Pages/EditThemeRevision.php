<?php

namespace App\Filament\Resources\ThemeRevisions\Pages;

use App\Domain\Theme\ThemeZipService;
use App\Filament\Resources\ThemeRevisions\ThemeRevisionResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditThemeRevision extends EditRecord
{
    protected static string $resource = ThemeRevisionResource::class;

    /** @var string|array|null Captured from form before save so we can process in afterSave. */
    protected string|array|null $pendingZipPath = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingZipPath = $data['zip_file'] ?? null;
        unset($data['zip_file']);
        return $data;
    }

    protected function afterSave(): void
    {
        $path = $this->pendingZipPath;
        $this->pendingZipPath = null;
        if (! $path || ! $this->record) {
            return;
        }
        $relativePath = is_array($path) ? ($path[0] ?? '') : $path;
        $relativePath = is_string($relativePath) ? trim($relativePath) : '';
        if ($relativePath === '') {
            return;
        }
        $sourcePath = Storage::disk('local')->path($relativePath);
        if (! is_file($sourcePath)) {
            return;
        }
        $service = ThemeZipService::fromConfig();
        $stored = $service->storeFromPath($sourcePath, $this->record->id, $this->record->original_filename ?: 'theme.zip');
        $this->record->update([
            'zip_path' => $stored,
            'original_filename' => $this->record->original_filename ?: 'theme.zip',
            'error' => null,
            'status' => 'pending',
        ]);
    }
}
