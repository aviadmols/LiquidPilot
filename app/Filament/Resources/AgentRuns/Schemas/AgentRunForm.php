<?php

namespace App\Filament\Resources\AgentRuns\Schemas;

use App\Models\AgentRun;
use App\Models\Project;
use App\Models\ThemeRevision;
use App\Models\ThemeSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgentRunForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Run setup')
                    ->description('Choose project and template (theme). The project\'s brand kit will be used. After Create you will see each step and can edit or re-run from any step.')
                    ->schema([
                        Select::make('project_id')
                            ->label('Project')
                            ->options(Project::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->live(),
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
                        Select::make('mode')
                            ->label('Mode')
                            ->options([
                                AgentRun::MODE_FULL => 'Full Run (entire homepage)',
                                AgentRun::MODE_TEST => 'Test Run (single section)',
                            ])
                            ->default(AgentRun::MODE_FULL)
                            ->required()
                            ->live(),
                        Select::make('selected_section_handle')
                            ->label('Section (Test Run only)')
                            ->options(fn ($get) => $get('theme_revision_id')
                                ? ThemeSection::where('theme_revision_id', $get('theme_revision_id'))->pluck('handle', 'handle')
                                : [])
                            ->required(fn ($get) => $get('mode') === AgentRun::MODE_TEST)
                            ->disabled(fn ($get) => $get('mode') !== AgentRun::MODE_TEST),
                        Select::make('output_format')
                            ->label('Output / Deliverables')
                            ->options([
                                AgentRun::OUTPUT_FULL_ZIP => 'Full theme ZIP',
                                AgentRun::OUTPUT_MEDIA_AND_JSON => 'Media + template JSON',
                                AgentRun::OUTPUT_BOTH => 'All (ZIP + JSON + media)',
                            ])
                            ->default(AgentRun::OUTPUT_FULL_ZIP),
                        Select::make('image_generator')
                            ->label('Image generator')
                            ->options([
                                AgentRun::IMAGE_GENERATOR_PLACEHOLDER => 'Placeholder (local)',
                                AgentRun::IMAGE_GENERATOR_NANOBANNA => 'NanoBanana (AI)',
                            ])
                            ->default(AgentRun::IMAGE_GENERATOR_PLACEHOLDER),
                        TextInput::make('max_images_per_run')
                            ->label('Max images per run (optional)')
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('No limit'),
                        Textarea::make('creative_brief')
                            ->label('Creative brief / Additional instructions')
                            ->placeholder('e.g. Minimal homepage, focus on large product images, short headlines')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
