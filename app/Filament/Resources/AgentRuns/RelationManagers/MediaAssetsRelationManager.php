<?php

namespace App\Filament\Resources\AgentRuns\RelationManagers;

use App\Models\MediaAsset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MediaAssetsRelationManager extends RelationManager
{
    protected static string $relationship = 'mediaAssets';

    protected static ?string $title = 'Media';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('filename')->label('File')->searchable(),
                TextColumn::make('purpose')->label('Purpose'),
                TextColumn::make('dimensions')
                    ->label('Size')
                    ->getStateUsing(fn (MediaAsset $r) => $r->width && $r->height ? "{$r->width}×{$r->height}" : '-'),
                TextColumn::make('mime')->label('Type'),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('created_at')->label('Added')->dateTime(),
            ])
            ->defaultSort('id')
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make()
                    ->label('Upload media')
                    ->form($this->getUploadFormSchema())
                    ->using(function (array $data): MediaAsset {
                        $run = $this->getOwnerRecord();
                        $run->load('themeRevision');
                        $revision = $run->themeRevision;
                        if (!$revision || !$revision->extracted_path || !is_dir($revision->extracted_path)) {
                            Notification::make()->title('Theme not extracted')->body('Run must use a theme revision that has been extracted.')->danger()->send();
                            throw new \RuntimeException('Theme extracted_path not available');
                        }
                        $uploadPath = $data['file'] ?? null;
                        if (is_array($uploadPath)) {
                            $uploadPath = $uploadPath[0] ?? null;
                        }
                        if (!$uploadPath) {
                            throw new \RuntimeException('No file uploaded');
                        }
                        $fullUploadPath = \Illuminate\Support\Facades\Storage::disk('local')->path($uploadPath);
                        if (!is_file($fullUploadPath)) {
                            throw new \RuntimeException('Uploaded file not found');
                        }
                        $purpose = trim($data['purpose'] ?? '');
                        $originalName = basename($fullUploadPath);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION) ?: 'png';
                        $safeName = $purpose !== ''
                            ? Str::slug($purpose) . '.' . $ext
                            : 'upload_' . date('Ymd_His') . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.' . $ext;
                        $assetsDir = $revision->extracted_path . \DIRECTORY_SEPARATOR . 'assets' . \DIRECTORY_SEPARATOR . 'generated';
                        if (!is_dir($assetsDir)) {
                            mkdir($assetsDir, 0755, true);
                        }
                        $destPath = $assetsDir . \DIRECTORY_SEPARATOR . $safeName;
                        $counter = 0;
                        while (file_exists($destPath)) {
                            $counter++;
                            $safeName = pathinfo($safeName, PATHINFO_FILENAME) . "_{$counter}." . pathinfo($safeName, PATHINFO_EXTENSION);
                            $destPath = $assetsDir . \DIRECTORY_SEPARATOR . $safeName;
                        }
                        File::copy($fullUploadPath, $destPath);
                        $relPath = 'assets/generated/' . str_replace('\\', '/', $safeName);
                        $mime = mime_content_type($destPath) ?: 'application/octet-stream';
                        $width = $height = null;
                        if (str_starts_with($mime, 'image/')) {
                            $info = @getimagesize($destPath);
                            if ($info) {
                                $width = $info[0];
                                $height = $info[1];
                            }
                        }
                        return MediaAsset::create([
                            'agent_run_id' => $run->id,
                            'filename' => $safeName,
                            'rel_path' => $relPath,
                            'width' => $width,
                            'height' => $height,
                            'mime' => $mime,
                            'purpose' => $purpose ?: $safeName,
                            'status' => 'ready',
                        ]);
                    })
                    ->after(function (): void {
                        Notification::make()->title('Media uploaded')->success()->send();
                    }),
            ])
            ->actions([
                \Filament\Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (MediaAsset $record): string => route('admin.download-run-media', ['agentRun' => $record->agent_run_id, 'mediaAsset' => $record->id]))
                    ->openUrlInNewTab(true),
                \Filament\Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (MediaAsset $record): string => route('admin.download-run-media', ['agentRun' => $record->agent_run_id, 'mediaAsset' => $record->id]))
                    ->openUrlInNewTab(true),
                \Filament\Tables\Actions\DeleteAction::make()
                    ->before(function (MediaAsset $record): void {
                        $run = $record->agentRun;
                        $run->load('themeRevision');
                        $base = $run->themeRevision?->extracted_path;
                        if ($base && $record->rel_path && is_file($base . \DIRECTORY_SEPARATOR . str_replace('/', \DIRECTORY_SEPARATOR, $record->rel_path))) {
                            File::delete($base . \DIRECTORY_SEPARATOR . str_replace('/', \DIRECTORY_SEPARATOR, $record->rel_path));
                        }
                    }),
            ])
            ->bulkActions([]);
    }

    protected function getUploadFormSchema(): array
    {
        return [
            Section::make('Upload media')
                ->schema([
                    FileUpload::make('file')
                        ->label('File')
                        ->required()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'])
                        ->maxSize(10 * 1024 * 1024)
                        ->storeFiles(false)
                        ->helperText('Image files (JPEG, PNG, GIF, WebP, SVG). Max 10 MB.'),
                    TextInput::make('purpose')
                        ->label('Purpose / name')
                        ->placeholder('e.g. hero_banner')
                        ->helperText('Optional. Used as filename in the theme.'),
                ]),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
