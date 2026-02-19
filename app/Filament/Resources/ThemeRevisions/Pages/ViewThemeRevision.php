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
            $actions[] = \Filament\Actions\Action::make('runScan')
                ->label('Run scan')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Run theme analysis')
                ->modalDescription('This will run or re-run the theme scan (extract, signature, catalog). The analysis log below will update. Make sure the queue worker is running (e.g. `php artisan queue:work`).')
                ->action(function () {
                    $record = $this->getRecord();
                    $record->update(['status' => 'pending', 'analysis_steps' => [], 'error' => null]);
                    AnalyzeThemeJob::dispatch($record->id);
                    Notification::make()
                        ->title('Scan started')
                        ->body('The analysis log below will refresh. If nothing appears, start the queue worker: php artisan queue:work')
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
