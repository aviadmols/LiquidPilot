<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\SettingsPage;
use Filament\Widgets\Widget;

class SettingsLinkWidget extends Widget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.settings-link-widget';

    public function getSettingsUrl(): string
    {
        return SettingsPage::getUrl();
    }
}
