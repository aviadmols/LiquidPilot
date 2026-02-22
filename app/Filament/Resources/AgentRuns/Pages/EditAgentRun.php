<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Callout;
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
        $statusHint = $this->getStatusHintCallout($record);
        $components = array_merge(
            $statusHint ? [$statusHint] : [],
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
                ->visible(fn () => $this->getRecord()->status !== AgentRun::STATUS_RUNNING)
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
                    SummarizeCatalogJob::dispatch($record->id)->onConnection('database');
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

    private function getStatusHintCallout(AgentRun $record): ?Callout
    {
        if ($record->status === AgentRun::STATUS_PENDING) {
            return Callout::make('Run is queued (Pending)')
                ->description('If the run never starts, the queue worker is probably not running. On the server run: php artisan queue:work database. Check the "AI Live Logs" section below.')
                ->warning();
        }
        if ($record->status === AgentRun::STATUS_FAILED) {
            $msg = $record->error
                ? 'Error: ' . $record->error
                : 'Run failed but no error was saved. Check "AI Live Logs" below and storage/logs/laravel.log on the server.';
            return Callout::make('Run failed')
                ->description($msg)
                ->danger();
        }
        return null;
    }
}
