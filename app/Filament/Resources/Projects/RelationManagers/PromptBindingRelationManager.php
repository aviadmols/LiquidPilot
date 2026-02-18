<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use App\Models\ModelConfig;
use App\Models\PromptTemplate;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromptBindingRelationManager extends RelationManager
{
    protected static string $relationship = 'promptBindings';

    protected static ?string $title = 'Prompt bindings';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('promptTemplate.key')->label('Prompt'),
                TextColumn::make('modelConfig.model_name')->label('Model config'),
            ])
            ->headerActions([
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ]);
    }

    public function form(Schema $schema): Schema
    {
        $owner = $this->getOwnerRecord();
        $modelConfigOptions = ModelConfig::where('project_id', $owner->id)->where('is_active', true)->pluck('model_name', 'id');
        return $schema
            ->components([
                Section::make()->schema([
                    Select::make('prompt_template_id')
                        ->label('Prompt template')
                        ->options(PromptTemplate::where('is_active', true)->pluck('key', 'id'))
                        ->required()
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set): void {
                            if (!$state) {
                                return;
                            }
                            $template = PromptTemplate::find($state);
                            if (!$template?->default_model_name) {
                                return;
                            }
                            $config = ModelConfig::where('project_id', $this->getOwnerRecord()->id)
                                ->where('is_active', true)
                                ->where('model_name', $template->default_model_name)
                                ->first();
                            if ($config) {
                                $set('model_config_id', $config->id);
                            }
                        }),
                    Select::make('model_config_id')
                        ->label('Model config')
                        ->options($modelConfigOptions)
                        ->required()
                        ->searchable(),
                ]),
            ]);
    }
}
