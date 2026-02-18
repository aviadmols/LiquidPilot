<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateAgentRun extends CreateRecord
{
    protected static string $resource = AgentRunResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['mode'] ?? AgentRun::MODE_FULL) === AgentRun::MODE_FULL) {
            $data['selected_section_handle'] = null;
        }
        $data['status'] = AgentRun::STATUS_PENDING;
        $data['max_images_per_run'] = isset($data['max_images_per_run']) && $data['max_images_per_run'] !== ''
            ? (int) $data['max_images_per_run']
            : null;
        return $data;
    }

    protected function afterCreate(): void
    {
        SummarizeCatalogJob::dispatch($this->record->id);
        Notification::make()
            ->title('Agent run started')
            ->body('You can follow each step below and edit or re-run from any step.')
            ->success()
            ->send();
        $this->redirect(AgentRunResource::getUrl('view', ['record' => $this->record]));
    }
}
