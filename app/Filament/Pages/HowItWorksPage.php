<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class HowItWorksPage extends Page
{
    protected static ?string $navigationLabel = 'How it works';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return 'heroicon-o-book-open';
    }

    protected static ?string $title = 'How it works';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'how-it-works';

    protected string $view = 'filament.pages.how-it-works';

    public function getHeading(): string
    {
        return 'Workflow & steps to generate your theme';
    }

    public function getSubheading(): ?string
    {
        return 'From uploading a base theme to exporting the final template.';
    }
}
