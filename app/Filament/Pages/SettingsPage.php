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

    public bool $keySavedSuccess = false;

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
        $key = $this->openrouter_api_key !== null ? trim((string) $this->openrouter_api_key) : '';
        if ($key === '') {
            Notification::make()->title('Enter a key to save, or leave blank to keep the current key.')->warning()->send();
            return;
        }

        try {
            Setting::setValue('openrouter_api_key', $key);
        } catch (\Throwable $e) {
            $this->keySavedSuccess = false;
            Notification::make()
                ->title('Could not save key')
                ->body($e->getMessage() . ' Alternatively set OPENROUTER_API_KEY in your .env or server environment.')
                ->danger()
                ->send();
            return;
        }

        $verified = Setting::getValue('openrouter_api_key');
        if ($verified !== null && $verified !== '') {
            $this->keySavedSuccess = true;
            Notification::make()->title('OpenRouter API key saved.')->success()->send();
            $this->openrouter_api_key = '';
        } else {
            $this->keySavedSuccess = false;
            Notification::make()
                ->title('Key could not be verified after save.')
                ->body('The key was written but could not be read back. Set OPENROUTER_API_KEY in .env or server environment as a fallback.')
                ->danger()
                ->send();
        }
    }
}
