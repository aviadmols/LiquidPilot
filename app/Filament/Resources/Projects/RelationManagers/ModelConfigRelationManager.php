<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModelConfigRelationManager extends RelationManager
{
    protected static string $relationship = 'modelConfigs';

    protected static ?string $title = 'Model configs';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('model_name')->label('Model'),
                TextColumn::make('provider'),
                TextColumn::make('temperature'),
                TextColumn::make('max_tokens'),
                TextColumn::make('is_active')->badge(),
            ])
            ->headerActions([
                \Filament\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('provider')->default('openrouter')->required()->maxLength(64),
                    TextInput::make('model_name')->label('Model name')->required()->maxLength(255),
                    TextInput::make('temperature')->numeric()->minValue(0)->maxValue(2)->step(0.1),
                    TextInput::make('max_tokens')->numeric()->minValue(1),
                    Toggle::make('json_mode')->label('JSON mode')->default(true),
                    Toggle::make('is_active')->label('Active')->default(true),
                ]),
            ]);
    }
}
