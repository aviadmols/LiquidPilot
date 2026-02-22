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
                        Select::make('theme_revision_id')
                            ->label('Template / Theme revision')
                            ->options(
                                ThemeRevision::where('status', 'ready')
                                    ->orderByDesc('scanned_at')
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [$r->id => $r->original_filename . ' (#' . $r->id . ')'])
                            )
                            ->required()
                            ->searchable(),
                        Select::make('output_format')
                            ->label('Output / Deliverables')
                            ->options([
                                \App\Models\AgentRun::OUTPUT_FULL_ZIP => 'Full theme ZIP',
                                \App\Models\AgentRun::OUTPUT_MEDIA_AND_JSON => 'Media + template JSON',
                                \App\Models\AgentRun::OUTPUT_BOTH => 'All (ZIP + JSON + media)',
                            ])
                            ->default(\App\Models\AgentRun::OUTPUT_FULL_ZIP),
                        Select::make('image_generator')
                            ->label('Image generator')
                            ->options([
                                \App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER => 'Placeholder (local)',
                                \App\Models\AgentRun::IMAGE_GENERATOR_NANOBANNA => 'NanoBanana (AI)',
                            ])
                            ->default(\App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER),
                        TextInput::make('max_images_per_run')
                            ->label('Max images per run (optional)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('No limit')
                            ->helperText('Only this many images will be generated; the rest will reuse them.'),
                        Textarea::make('creative_brief')
                            ->label('Creative brief / Additional instructions')
                            ->placeholder('e.g. Minimal homepage, focus on large product images, short headlines')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Project $record, array $data) {
                        $run = \App\Models\AgentRun::create([
                            'project_id' => $record->id,
                            'theme_revision_id' => $data['theme_revision_id'],
                            'mode' => \App\Models\AgentRun::MODE_FULL,
                            'output_format' => $data['output_format'] ?? \App\Models\AgentRun::OUTPUT_FULL_ZIP,
                            'creative_brief' => $data['creative_brief'] ?? null,
                            'image_generator' => $data['image_generator'] ?? \App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER,
                            'max_images_per_run' => isset($data['max_images_per_run']) && $data['max_images_per_run'] !== '' ? (int) $data['max_images_per_run'] : null,
                            'status' => \App\Models\AgentRun::STATUS_PENDING,
                        ]);
                        \App\Jobs\SummarizeCatalogJob::dispatch($run->id)->onConnection('database');
                        Notification::make()->title('Agent run started')->success()->send();
                        return redirect(\App\Filament\Resources\AgentRuns\AgentRunResource::getUrl('view', ['record' => $run]));
                    }),
                \Filament\Actions\Action::make('testRun')
                    ->label('Test Run')
                    ->icon('heroicon-o-beaker')
                    ->form([
                        Select::make('theme_revision_id')
                            ->label('Template / Theme revision')
                            ->options(
                                ThemeRevision::where('status', 'ready')
                                    ->orderByDesc('scanned_at')
                                    ->get()
                                    ->mapWithKeys(fn ($r) => [$r->id => $r->original_filename . ' (#' . $r->id . ')'])
                            )
                            ->required()
                            ->searchable()
                            ->live(),
                        Select::make('section_handle')
                            ->label('Section')
                            ->options(fn ($get) => $get('theme_revision_id')
                                ? \App\Models\ThemeSection::where('theme_revision_id', $get('theme_revision_id'))->pluck('handle', 'handle')
                                : [])
                            ->required()
                            ->disabled(fn ($get) => ! $get('theme_revision_id')),
                        Select::make('output_format')
                            ->label('Output / Deliverables')
                            ->options([
                                \App\Models\AgentRun::OUTPUT_FULL_ZIP => 'Full theme ZIP',
                                \App\Models\AgentRun::OUTPUT_MEDIA_AND_JSON => 'Media + template JSON',
                                \App\Models\AgentRun::OUTPUT_BOTH => 'All (ZIP + JSON + media)',
                            ])
                            ->default(\App\Models\AgentRun::OUTPUT_FULL_ZIP),
                        Select::make('image_generator')
                            ->label('Image generator')
                            ->options([
                                \App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER => 'Placeholder (local)',
                                \App\Models\AgentRun::IMAGE_GENERATOR_NANOBANNA => 'NanoBanana (AI)',
                            ])
                            ->default(\App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER),
                        TextInput::make('max_images_per_run')
                            ->label('Max images per run (optional)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('No limit')
                            ->helperText('Only this many images will be generated; the rest will reuse them.'),
                        Textarea::make('creative_brief')
                            ->label('Creative brief / Additional instructions')
                            ->placeholder('e.g. Minimal homepage, focus on large product images')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->action(function (Project $record, array $data) {
                        $run = \App\Models\AgentRun::create([
                            'project_id' => $record->id,
                            'theme_revision_id' => $data['theme_revision_id'],
                            'mode' => \App\Models\AgentRun::MODE_TEST,
                            'selected_section_handle' => $data['section_handle'],
                            'output_format' => $data['output_format'] ?? \App\Models\AgentRun::OUTPUT_FULL_ZIP,
                            'creative_brief' => $data['creative_brief'] ?? null,
                            'image_generator' => $data['image_generator'] ?? \App\Models\AgentRun::IMAGE_GENERATOR_PLACEHOLDER,
                            'max_images_per_run' => isset($data['max_images_per_run']) && $data['max_images_per_run'] !== '' ? (int) $data['max_images_per_run'] : null,
                            'status' => \App\Models\AgentRun::STATUS_PENDING,
                        ]);
                        \App\Jobs\SummarizeCatalogJob::dispatch($run->id)->onConnection('database');
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
