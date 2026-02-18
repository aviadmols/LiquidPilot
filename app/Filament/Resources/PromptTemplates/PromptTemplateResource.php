<?php

namespace App\Filament\Resources\PromptTemplates;

use App\Filament\Resources\PromptTemplates\Pages\ManagePromptTemplates;
use App\Models\PromptTemplate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromptTemplateResource extends Resource
{
    protected static ?string $model = PromptTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('key')->required()->maxLength(255),
                    \Filament\Forms\Components\Textarea::make('template_text')->rows(10)->required(),
                    TextInput::make('version')->maxLength(16)->default('1'),
                    Toggle::make('is_active')->default(true),
                    TextInput::make('default_model_name')
                        ->label('Default model (module)')
                        ->placeholder('e.g. openai/gpt-4o-mini')
                        ->helperText('OpenRouter model name used as default when creating a prompt binding for this task.')
                        ->maxLength(128),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('key')->searchable(),
                TextColumn::make('default_model_name')->label('Default module')->placeholder('—'),
                TextColumn::make('version'),
                TextColumn::make('is_active')->badge(),
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
            'index' => ManagePromptTemplates::route('/'),
        ];
    }
}
