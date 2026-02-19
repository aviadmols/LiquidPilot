<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Jobs\SummarizeCatalogJob;
use App\Models\AgentRun;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAgentRun extends CreateRecord
{
    protected static string $resource = AgentRunResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Flatten if form state was nested under section keys (e.g. two Sections)
        if (! isset($data['project_id'])) {
            $flat = [];
            foreach ($data as $value) {
                if (is_array($value) && (isset($value['project_id']) || isset($value['mode']))) {
                    $flat = array_merge($flat, $value);
                }
            }
            if (! empty($flat)) {
                $data = $flat;
            } elseif (count($data) === 1) {
                $nested = reset($data);
                if (is_array($nested) && isset($nested['project_id'])) {
                    $data = $nested;
                }
            }
        }
        if (empty($data['project_id']) || empty($data['theme_revision_id'])) {
            throw ValidationException::withMessages([
                'data.project_id' => [__('Please select a Project.')],
                'data.theme_revision_id' => [__('Please select a Template / Theme revision.')],
            ]);
        }
        if (($data['mode'] ?? AgentRun::MODE_FULL) === AgentRun::MODE_FULL) {
            $data['selected_section_handle'] = null;
        }
        $data['status'] = AgentRun::STATUS_PENDING;
        $data['brand_kit_id'] = ! empty($data['brand_kit_id']) ? $data['brand_kit_id'] : null;
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
