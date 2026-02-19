<?php

namespace App\Filament\Resources\ThemeRevisions\Pages;

use App\Domain\Theme\ThemeCatalogReportBuilder;
use App\Filament\Resources\ThemeRevisions\ThemeRevisionResource;
use App\Jobs\AnalyzeThemeJob;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Response;

class ViewThemeRevision extends ViewRecord
{
    protected static string $resource = ThemeRevisionResource::class;

    protected function getHeaderActions(): array
    {
        $actions = [EditAction::make()];
        $record = $this->getRecord();
        if (in_array($record->status, ['pending', 'failed'], true)) {
            $actions[] = \Filament\Actions\Action::make('runScanNow')
                ->label('Run scan now')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Run theme analysis now')
                ->modalDescription('The scan will run in this browser tab and may take up to a minute. No queue worker needed.')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['status' => 'pending', 'analysis_steps' => [], 'error' => null]);
                    try {
                        AnalyzeThemeJob::dispatchSync($record->id);
                        $record->refresh();
                        Notification::make()
                            ->title($record->status === 'ready' ? 'Scan completed' : 'Scan finished')
                            ->body($record->status === 'ready' ? 'Theme analyzed successfully.' : ($record->error ?? 'Check the analysis log.'))
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Scan failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                    $record->refresh();
                    return redirect()->to(ThemeRevisionResource::getUrl('view', ['record' => $record]));
                });
            $actions[] = \Filament\Actions\Action::make('runScan')
                ->label('Run in background')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Run theme analysis in background')
                ->modalDescription('Requires a running queue worker (php artisan queue:work). The analysis log will auto-refresh when the job runs.')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['status' => 'pending', 'analysis_steps' => [], 'error' => null]);
                    AnalyzeThemeJob::dispatch($record->id);
                    Notification::make()
                        ->title('Scan queued')
                        ->body('Start the queue worker (php artisan queue:work) if the log does not update.')
                        ->success()
                        ->send();
                });
        }
        if ($record->status === 'ready') {
            $actions[] = \Filament\Actions\Action::make('downloadReport')
                ->label('Download report')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    $record = $this->getRecord();
                    $markdown = (new ThemeCatalogReportBuilder())->buildMarkdown($record);
                    $filename = 'catalog-report-' . $record->id . '-' . $record->original_filename . '.md';
                    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '-', $filename);
                    return Response::streamDownload(
                        fn () => print($markdown),
                        $filename,
                        ['Content-Type' => 'text/markdown; charset=utf-8']
                    );
                });
        }
        return $actions;
    }

    public function content(Schema $schema): Schema
    {
        $record = $this->getRecord();
        $components = [
            $this->hasInfolist()
                ? $this->getInfolistContentComponent()
                : $this->getFormContentComponent(),
            Section::make('Analysis log')
                ->description('Progress and steps for this theme revision. Auto-refreshes while pending or analyzing. Theme scan is rule-based (no AI). To control the AI model used for Agent Runs (plan/compose), use Settings for the API key and the Project\'s Model config.')
                ->schema([
                    Livewire::make(\App\Livewire\ThemeRevisionAnalysisLog::class, ['themeRevisionId' => $record->id]),
                ])
                ->collapsible()
                ->collapsed(false),
            Section::make('Catalog summary report')
                ->description($record->status !== 'ready' ? 'Scan must complete before the report is available.' : null)
                ->schema([
                    Livewire::make(\App\Livewire\CatalogReport::class, ['themeRevisionId' => $record->id]),
                ])
                ->collapsible()
                ->collapsed(false),
        ];
        return $schema->components($components);
    }
}
