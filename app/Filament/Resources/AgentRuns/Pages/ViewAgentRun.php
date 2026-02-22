<?php

namespace App\Filament\Resources\AgentRuns\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

class ViewAgentRun extends ViewRecord
{
    protected static string $resource = AgentRunResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [EditAction::make()];
        $export = $this->getRecord()->export;
        if ($export) {
            if ($export->zip_path && $this->isSafeExportPath($export->zip_path)) {
                $actions[] = \Filament\Actions\Action::make('downloadThemeZip')
                    ->label('Download theme ZIP')
                    ->icon('heroicon-o-archive-box')
                    ->action(function () {
                        $export = $this->getRecord()->export;
                        if ($export && $export->zip_path && $this->isSafeExportPath($export->zip_path)) {
                            return Storage::disk('local')->download(
                                $export->zip_path,
                                'theme_run_' . $this->getRecord()->id . '.zip'
                            );
                        }
                    });
            }
            if ($export->template_json_path && $this->isSafeExportPath($export->template_json_path)) {
                $actions[] = \Filament\Actions\Action::make('downloadTemplateJson')
                    ->label('Download template JSON')
                    ->icon('heroicon-o-document-text')
                    ->action(function () {
                        $export = $this->getRecord()->export;
                        if ($export && $export->template_json_path && $this->isSafeExportPath($export->template_json_path)) {
                            return Storage::disk('local')->download(
                                $export->template_json_path,
                                'index_run_' . $this->getRecord()->id . '.json'
                            );
                        }
                    });
            }
            if ($export->media_archive_path && $this->isSafeExportPath($export->media_archive_path)) {
                $actions[] = \Filament\Actions\Action::make('downloadMedia')
                    ->label('Download media')
                    ->icon('heroicon-o-photo')
                    ->action(function () {
                        $export = $this->getRecord()->export;
                        if ($export && $export->media_archive_path && $this->isSafeExportPath($export->media_archive_path)) {
                            return Storage::disk('local')->download(
                                $export->media_archive_path,
                                'media_run_' . $this->getRecord()->id . '.zip'
                            );
                        }
                    });
            }
        }
        return $actions;
    }

    private function isSafeExportPath(string $path): bool
    {
        $path = str_replace('\\', '/', $path);
        return str_starts_with($path, 'exports/') && !str_contains($path, '..');
    }

    public function content(Schema $schema): Schema
    {
        $record = $this->getRecord();
        $components = [
            $this->hasInfolist()
                ? $this->getInfolistContentComponent()
                : $this->getFormContentComponent(),
            Section::make('AI Live Logs')
                ->description('Logs from the agent pipeline (summary, plan, compose). Use these to debug why a run is stuck or failed.')
                ->schema([
                    Livewire::make(\App\Livewire\AiLogsList::class, ['agentRunId' => $record->id]),
                ])
                ->collapsible()
                ->collapsed(false),
            $this->getRelationManagersContentComponent(),
        ];
        return $schema->components($components);
    }
}
