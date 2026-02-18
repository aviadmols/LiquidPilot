<?php

namespace App\Filament\Resources\BrandKits;

use App\Filament\Resources\BrandKits\Pages\ManageBrandKits;
use App\Models\BrandKit;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
                    Textarea::make('colors_json')->rows(3),
                    Textarea::make('typography_json')->rows(2),
                    Textarea::make('imagery_style_json')->rows(2),
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
