<?php

namespace App\Livewire;

use App\Models\ThemeRevision;
use Livewire\Component;

class ThemeRevisionAnalysisLog extends Component
{
    public int $themeRevisionId;

    public function mount(int $themeRevisionId): void
    {
        $this->themeRevisionId = $themeRevisionId;
    }

    public function getRevisionProperty(): ?ThemeRevision
    {
        return ThemeRevision::find($this->themeRevisionId);
    }

    public function getStepsProperty(): array
    {
        $revision = $this->revision;
        if (! $revision || ! is_array($revision->analysis_steps)) {
            return [];
        }
        return $revision->analysis_steps;
    }

    public function getShouldPollProperty(): bool
    {
        $revision = $this->revision;
        if (! $revision) {
            return false;
        }
        return in_array($revision->status, ['pending', 'analyzing'], true);
    }

    public function render()
    {
        return view('livewire.theme-revision-analysis-log');
    }
}
