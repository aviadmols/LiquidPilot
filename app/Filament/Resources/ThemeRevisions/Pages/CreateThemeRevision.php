<?php

namespace App\Filament\Resources\ThemeRevisions\Pages;

use App\Domain\Theme\ThemeZipService;
use App\Filament\Resources\ThemeRevisions\ThemeRevisionResource;
use App\Jobs\AnalyzeThemeJob;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateThemeRevision extends CreateRecord
{
    protected static string $resource = ThemeRevisionResource::class;

    /** @var string|array|null Temporary file path from upload (before create). */
    public string|array|null $pendingZipPath = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $file = $data['zip_file'] ?? null;
        $this->pendingZipPath = $file;
        unset($data['zip_file']);
        $data['original_filename'] = is_array($file) ? (basename($file[0] ?? 'theme.zip')) : 'theme.zip';
        $data['zip_path'] = '';
        $data['status'] = 'pending';
        $data['project_id'] = null;
        return $data;
    }

    protected function afterCreate(): void
    {
        $path = $this->pendingZipPath;
        if ($path && $this->record) {
            $sourcePath = is_array($path) ? (Storage::disk('local')->path($path[0] ?? '')) : Storage::disk('local')->path($path);
            if (is_file($sourcePath)) {
                $stored = ThemeZipService::fromConfig()->storeFromPath($sourcePath, $this->record->id, $this->record->original_filename);
                $this->record->update(['zip_path' => $stored]);
            }
            AnalyzeThemeJob::dispatch($this->record->id);
        }
        $this->pendingZipPath = null;
    }
}
