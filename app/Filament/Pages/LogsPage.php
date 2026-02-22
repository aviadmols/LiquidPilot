<?php

namespace App\Filament\Pages;

use App\Filament\Resources\AgentRuns\AgentRunResource;
use App\Models\AIActionLog;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

class LogsPage extends Page
{
    protected static ?string $navigationLabel = 'Logs';

    protected static ?string $title = 'Logs';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'logs';

    protected string $view = 'filament.pages.logs';

    public function getHeading(): string
    {
        return 'System logs';
    }

    public function getSubheading(): ?string
    {
        return 'AI activity and Laravel application log.';
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-document-text';
    }

    public function getAiLogs(): LengthAwarePaginator
    {
        return AIActionLog::with('agentRun')
            ->orderByDesc('id')
            ->paginate(50);
    }

    public function getLaravelLogLines(): array
    {
        $path = storage_path('logs/laravel.log');
        if (! File::exists($path)) {
            return ['(Log file not found: storage/logs/laravel.log)'];
        }
        $content = File::get($path);
        $lines = explode("\n", $content);
        $lines = array_slice($lines, -200);
        return array_values(array_filter($lines));
    }

    public function getAgentRunViewUrl(int $agentRunId): string
    {
        $run = \App\Models\AgentRun::find($agentRunId);
        if (! $run) {
            return '#';
        }

        return AgentRunResource::getUrl('view', ['record' => $run]);
    }
}
