<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class SettingsPage extends Page
{
    protected static ?string $navigationLabel = 'Settings';

    protected static ?string $title = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'settings';

    protected string $view = 'filament.pages.settings';

    public ?string $openrouter_api_key = null;

    public function mount(): void
    {
        $this->openrouter_api_key = '';
    }

    public function hasOpenRouterKey(): bool
    {
        return Setting::getValue('openrouter_api_key') !== null;
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-cog-6-tooth';
    }

    public function getHeading(): string
    {
        return 'Settings';
    }

    public function getSubheading(): ?string
    {
        return 'Global API keys and options.';
    }

    public function saveOpenRouterKey(): void
    {
        $key = $this->openrouter_api_key !== null ? trim($this->openrouter_api_key) : '';
        if ($key !== '') {
            Setting::setValue('openrouter_api_key', $key);
            Notification::make()->title('OpenRouter API key saved.')->success()->send();
            $this->openrouter_api_key = '';
        } else {
            Notification::make()->title('Enter a key to save, or leave blank to keep the current key.')->warning()->send();
        }
    }
}
