<?php

namespace App\Filament\Resources\ThemeRevisions\Pages;

use App\Domain\Theme\ThemeCatalogReportBuilder;
use App\Filament\Resources\ThemeRevisions\ThemeRevisionResource;
use Filament\Actions\EditAction;
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
