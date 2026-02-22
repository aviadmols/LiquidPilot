<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditAgentRun extends EditRecord
{
    protected static string $resource = AgentRunResource::class;

    public function form(Schema $schema): Schema
    {
        $base = parent::form($schema);
        $record = $this->getRecord();
        $components = array_merge(
            $base->getComponents(),
            [
                Section::make('AI Live Logs')
                    ->description('Logs from the agent pipeline. Use these to debug why a run is stuck or failed.')
                    ->schema([
                        Livewire::make(\App\Livewire\AiLogsList::class, ['agentRunId' => $record->id]),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]
        );
        return $base->components($components);
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();
        $actions = [
            ViewAction::make(),
            \Filament\Actions\Action::make('runAgain')
                ->label('Run again')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => $record->status !== AgentRun::STATUS_RUNNING)
                ->requiresConfirmation()
                ->modalHeading('Run agent again')
                ->modalDescription('This will re-run the full pipeline (summary → plan → compose) from the start. The run will be queued; you will be taken to the View page where you can follow progress in "AI Live Logs".')
                ->action(function (): void {
                    $record = $this->getRecord();
                    $record->update([
                        'status' => AgentRun::STATUS_RUNNING,
                        'error' => null,
                        'progress' => 0,
                    ]);
                    SummarizeCatalogJob::dispatch($record->id);
                    Notification::make()
                        ->title('Run queued')
                        ->body('You will be taken to the View page. Open the "AI Live Logs" section to see what the agent is doing.')
                        ->success()
                        ->send();
                    $this->redirect(AgentRunResource::getUrl('view', ['record' => $record]));
                }),
            DeleteAction::make(),
        ];
        return $actions;
    }
}
