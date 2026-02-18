<?php

namespace App\Filament\Resources\BrandKits;

use App\Filament\Resources\BrandKits\Pages\ManageBrandKits;
use App\Models\BrandKit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BrandKitResource extends Resource
{
    protected static ?string $model = BrandKit::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    Select::make('project_id')->relationship('project', 'name')->required(),
                    TextInput::make('brand_name')->maxLength(255),
                    TextInput::make('brand_type')->maxLength(255),
                    TextInput::make('industry')->maxLength(255),
                    TextInput::make('tone_of_voice')->maxLength(255),
                    TextInput::make('language')->maxLength(16),
                    Section::make('Colors')->schema([
                        TextInput::make('colors_primary')
                            ->label('Primary')
                            ->type('color')
                            ->default('#4F46E5'),
                        TextInput::make('colors_secondary')
                            ->label('Secondary')
                            ->type('color')
                            ->default('#7C3AED'),
                        TextInput::make('colors_background')
                            ->label('Background (optional)')
                            ->type('color'),
                    ])->columns(3),
                    Section::make('Typography')->schema([
                        TextInput::make('typography_heading_font')
                            ->label('Heading font (optional)')
                            ->maxLength(255),
                        TextInput::make('typography_body_font')
                            ->label('Body font (optional)')
                            ->maxLength(255),
                        Textarea::make('typography_notes')
                            ->label('Typography notes (optional)')
                            ->rows(2),
                    ])->columns(2)->collapsible(),
                    Section::make('Imagery style')->schema([
                        TextInput::make('imagery_keywords')
                            ->label('Keywords (comma-separated)'),
                        TextInput::make('imagery_vibe')
                            ->label('Vibe / style'),
                    ])->columns(2),
                    Section::make('Logo')->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('brand_kits')
                            ->visibility('public')
                            ->maxSize(2048),
                        Textarea::make('logo_design_notes')
                            ->label('Logo design notes (for template generation)')
                            ->rows(2),
                    ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name'),
                TextColumn::make('brand_name'),
                TextColumn::make('industry'),
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
            'index' => ManageBrandKits::route('/'),
        ];
    }
}
