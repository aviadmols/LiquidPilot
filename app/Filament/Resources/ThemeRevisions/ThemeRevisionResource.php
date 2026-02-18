<?php

namespace App\Filament\Resources\ThemeRevisions;

use App\Domain\Theme\ThemeZipService;
use App\Filament\Resources\ThemeRevisions\Pages\ManageThemeRevisions;
use App\Jobs\AnalyzeThemeJob;
use App\Models\ThemeRevision;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
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
                    Select::make('project_id')->relationship('project', 'name')->required(),
                    FileUpload::make('zip_file')
                        ->label('Theme ZIP')
                        ->acceptedFileTypes(['application/zip'])
                        ->maxSize(config('theme.zip_max_size_bytes', 100 * 1024 * 1024))
                        ->required()
                        ->storeFiles(false),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name'),
                TextColumn::make('original_filename'),
                TextColumn::make('status')->badge(),
                TextColumn::make('signature_sha256')->limit(12),
                TextColumn::make('scanned_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
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
        ];
    }
}
