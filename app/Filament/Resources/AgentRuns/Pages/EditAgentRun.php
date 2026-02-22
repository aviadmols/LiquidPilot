<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditAgentRun extends EditRecord
{
    protected static string $resource = AgentRunResource::class;

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
                ->modalDescription('This will re-run the full pipeline (summary → plan → compose) from the start. The run will be queued; open the View page to follow progress.')
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
                        ->body('Open the View page to follow progress.')
                        ->success()
                        ->send();
                    $this->redirect(AgentRunResource::getUrl('view', ['record' => $record]));
                }),
            DeleteAction::make(),
        ];
        return $actions;
    }
}
