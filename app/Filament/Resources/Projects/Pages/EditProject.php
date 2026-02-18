<?php

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Projects\ProjectResource;
use App\Models\ProjectSecret;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextInput;
use Filament\Schemas\Schema;

class EditProject extends EditRecord
{
    protected static string $resource = ProjectResource::class;

    protected ?string $pendingOpenRouterKey = null;

    public function form(Schema $schema): Schema
    {
        $base = parent::form($schema);
        $components = array_merge(
            $base->getComponents(),
            [
                Section::make('API & AI')
                    ->schema([
                        TextInput::make('openrouter_api_key')
                            ->label('OpenRouter API Key')
                            ->password()
                            ->placeholder('Leave blank to keep current')
                            ->dehydrated(fn ($state) => filled($state)),
                    ])
                    ->collapsible(),
            ]
        );
        return $base->components($components);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingOpenRouterKey = $data['openrouter_api_key'] ?? null;
        unset($data['openrouter_api_key']);
        return $data;
    }

    public function mutateFormDataBeforeFill(array $data): array
    {
        $secret = ProjectSecret::where('project_id', $this->record->id)
            ->where('key', 'openrouter_api_key')
            ->first();
        $data['openrouter_api_key'] = $secret ? $secret->getDecryptedValue() : '';
        return $data;
    }

    protected function afterSave(): void
    {
        $key = $this->pendingOpenRouterKey;
        if ($key === null || $key === '') {
            return;
        }
        $secret = ProjectSecret::firstOrNew([
            'project_id' => $this->record->id,
            'key' => 'openrouter_api_key',
        ]);
        $secret->setDecryptedValue($key);
        $secret->save();
    }
}
