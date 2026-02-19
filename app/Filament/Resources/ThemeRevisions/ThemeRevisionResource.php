<?php

namespace App\Filament\Resources\ThemeRevisions;

use App\Domain\Theme\ThemeZipService;
use App\Filament\Resources\ThemeRevisions\Pages\ManageThemeRevisions;
use App\Filament\Resources\ThemeRevisions\Pages\ViewThemeRevision;
use App\Filament\Resources\ThemeRevisions\Schemas\ThemeRevisionInfolist;
use App\Jobs\AnalyzeThemeJob;
use App\Models\ThemeRevision;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class ThemeRevisionResource extends Resource
{
    protected static ?string $model = ThemeRevision::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBox;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    FileUpload::make('zip_file')
                        ->label('Theme ZIP')
                        ->disk('local')
                        ->directory('livewire-tmp/theme-zip')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip',
                            'application/x-zip-compressed',
                            'application/octet-stream',
                        ])
                        ->maxSize(config('theme.zip_max_size_bytes', 100 * 1024 * 1024))
                        ->required(fn (): bool => str_ends_with(request()->route()?->getName() ?? '', 'create'))
                        ->storeFiles(false),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')->label('Project')->placeholder('-'),
                TextColumn::make('original_filename'),
                TextColumn::make('status')->badge(),
                TextColumn::make('signature_sha256')->limit(12),
                TextColumn::make('scanned_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageThemeRevisions::route('/'),
            'create' => Pages\CreateThemeRevision::route('/create'),
            'edit' => Pages\EditThemeRevision::route('/{record}/edit'),
            'view' => ViewThemeRevision::route('/{record}'),
        ];
    }
}
