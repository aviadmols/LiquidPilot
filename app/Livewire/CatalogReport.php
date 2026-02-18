<?php

namespace App\Livewire;

use App\Domain\Theme\ThemeCatalogReportBuilder;
use App\Models\ThemeRevision;
use Livewire\Component;

class CatalogReport extends Component
{
    public int $themeRevisionId;

    public function mount(int $themeRevisionId): void
    {
        $this->themeRevisionId = $themeRevisionId;
    }

    public function getReportProperty(): array
    {
        $revision = ThemeRevision::find($this->themeRevisionId);
        if (! $revision) {
            return ['sections' => []];
        }
        return (new ThemeCatalogReportBuilder())->build($revision);
    }

    public function render()
    {
        return view('livewire.catalog-report');
    }
}
