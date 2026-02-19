<?php

namespace App\Filament\Resources\AgentRuns\RelationManagers;

use App\Jobs\ComposeSectionsJob;
use App\Jobs\PlanHomepageJob;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use App\Models\AgentStep;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgentStepsRelationManager extends RelationManager
{
    protected static string $relationship = 'agentSteps';

    protected static ?string $title = 'Agent steps – choices & approval';

    protected static ?string $recordTitleAttribute = 'step_key';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('step_key')
                    ->label('Step')
                    ->formatStateUsing(fn (AgentStep $record) => $record->getStepLabel()),
                TextColumn::make('status')->label('Status'),
                TextColumn::make('choices_display')
                    ->label('What the agent chose')
                    ->getStateUsing(fn (AgentStep $record) => $record->getChoicesBullets())
                    ->formatStateUsing(function ($state): string {
                        if (!is_array($state)) {
                            return (string) $state;
                        }
                        return implode("\n", array_map(fn ($s) => '• ' . $s, $state));
                    })
                    ->wrap()
                    ->columnSpan(2),
                TextColumn::make('updated_at')->label('Updated')->dateTime(),
            ])
            ->defaultSort('id')
            ->headerActions([])
            ->actions([
                Action::make('editOutput')
                    ->label('Edit / Update')
                    ->icon('heroicon-o-pencil-square')
                    ->fillForm(fn (AgentStep $record) => [
                        'output_json_text' => json_encode($record->output_json ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                    ])
                    ->form([
                        Textarea::make('output_json_text')
                            ->label('Step output (JSON)')
                            ->rows(15)
                            ->required(),
                    ])
                    ->action(function (AgentStep $record, array $data): void {
                        $decoded = json_decode($data['output_json_text'], true);
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            Notification::make()->title('Invalid JSON')->body(json_last_error_msg())->danger()->send();
                            return;
                        }
                        $record->update(['output_json' => $decoded]);
                        Notification::make()->title('Step updated')->success()->send();
                    }),
                Action::make('rerunFromHere')
                    ->label('Re-run from here')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn (AgentStep $record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('Re-run from this step')
                    ->modalDescription(fn (AgentStep $record) => 'The step "' . $record->getStepLabel() . '" and all following steps will run again. Continue?')
                    ->action(function (AgentStep $record): void {
                        $run = $record->agentRun;
                        if ($run->status === AgentRun::STATUS_RUNNING) {
                            Notification::make()->title('Run is still in progress')->danger()->send();
                            return;
                        }
                        $run->update(['status' => AgentRun::STATUS_RUNNING, 'error' => null]);
                        match ($record->step_key) {
                            'summary' => SummarizeCatalogJob::dispatch($run->id),
                            'plan' => PlanHomepageJob::dispatch($run->id),
                            'compose' => ComposeSectionsJob::dispatch($run->id),
                            default => null,
                        };
                        Notification::make()->title('Re-run has been queued')->success()->send();
                    }),
            ])
            ->bulkActions([]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }
}
