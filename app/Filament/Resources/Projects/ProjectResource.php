<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\EditProject;
use App\Filament\Resources\Projects\Pages\ManageProjects;
use App\Filament\Resources\Projects\RelationManagers\ModelConfigRelationManager;
use App\Filament\Resources\Projects\RelationManagers\PromptBindingRelationManager;
use App\Models\Project;
use App\Models\ThemeRevision;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('locale')->default('en')->maxLength(16),
                    Select::make('status')->options(['active' => 'Active', 'archived' => 'Archived'])->default('active'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('locale'),
                TextColumn::make('status'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                \Filament\Actions\Action::make('fullRun')
                    ->label('Full Run')
                    ->icon('heroicon-o-play')
                    ->form([
                        Select::make('output_format')
                            ->label('Output / Deliverables')
                            ->options([
                                \App\Models\AgentRun::OUTPUT_FULL_ZIP => 'Full theme ZIP',
                                \App\Models\AgentRun::OUTPUT_MEDIA_AND_JSON => 'Media + template JSON',
                                \App\Models\AgentRun::OUTPUT_BOTH => 'All (ZIP + JSON + media)',
                            ])
                            ->default(\App\Models\AgentRun::OUTPUT_FULL_ZIP),
                        Textarea::make('creative_brief')
                            ->label('Creative brief / Additional instructions')
                            ->placeholder('e.g. Minimal homepage, focus on large product images, short headlines')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Project $record, array $data) {
                        $revision = ThemeRevision::where('project_id', $record->id)->where('status', 'ready')->latest()->first();
                        if (!$revision) {
                            Notification::make()->title('No ready theme revision')->danger()->send();
                            return;
                        }
                        $run = \App\Models\AgentRun::create([
                            'project_id' => $record->id,
                            'theme_revision_id' => $revision->id,
                            'mode' => \App\Models\AgentRun::MODE_FULL,
                            'output_format' => $data['output_format'] ?? \App\Models\AgentRun::OUTPUT_FULL_ZIP,
                            'creative_brief' => $data['creative_brief'] ?? null,
                            'status' => \App\Models\AgentRun::STATUS_PENDING,
                        ]);
                        \App\Jobs\SummarizeCatalogJob::dispatch($run->id);
                        Notification::make()->title('Agent run started')->success()->send();
                        return redirect(\App\Filament\Resources\AgentRuns\AgentRunResource::getUrl('view', ['record' => $run]));
                    }),
                \Filament\Actions\Action::make('testRun')
                    ->label('Test Run')
                    ->icon('heroicon-o-beaker')
                    ->form([
                        Select::make('section_handle')->label('Section')->options(fn (Project $record) => \App\Models\ThemeSection::whereHas('themeRevision', fn (Builder $q) => $q->where('project_id', $record->id))->pluck('handle', 'handle'))->required(),
                        Select::make('output_format')
                            ->label('Output / Deliverables')
                            ->options([
                                \App\Models\AgentRun::OUTPUT_FULL_ZIP => 'Full theme ZIP',
                                \App\Models\AgentRun::OUTPUT_MEDIA_AND_JSON => 'Media + template JSON',
                                \App\Models\AgentRun::OUTPUT_BOTH => 'All (ZIP + JSON + media)',
                            ])
                            ->default(\App\Models\AgentRun::OUTPUT_FULL_ZIP),
                        Textarea::make('creative_brief')
                            ->label('Creative brief / Additional instructions')
                            ->placeholder('e.g. Minimal homepage, focus on large product images')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Project $record, array $data) {
                        $revision = ThemeRevision::where('project_id', $record->id)->where('status', 'ready')->latest()->first();
                        if (!$revision) {
                            Notification::make()->title('No ready theme revision')->danger()->send();
                            return;
                        }
                        $run = \App\Models\AgentRun::create([
                            'project_id' => $record->id,
                            'theme_revision_id' => $revision->id,
                            'mode' => \App\Models\AgentRun::MODE_TEST,
                            'selected_section_handle' => $data['section_handle'],
                            'output_format' => $data['output_format'] ?? \App\Models\AgentRun::OUTPUT_FULL_ZIP,
                            'creative_brief' => $data['creative_brief'] ?? null,
                            'status' => \App\Models\AgentRun::STATUS_PENDING,
                        ]);
                        \App\Jobs\SummarizeCatalogJob::dispatch($run->id);
                        Notification::make()->title('Test run started')->success()->send();
                        return redirect(\App\Filament\Resources\AgentRuns\AgentRunResource::getUrl('view', ['record' => $run]));
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ModelConfigRelationManager::class,
            PromptBindingRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProjects::route('/'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
