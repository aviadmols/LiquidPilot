<?php

namespace App\Filament\Resources\ThemeRevisions\Pages;

use App\Filament\Resources\ThemeRevisions\ThemeRevisionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ManageThemeRevisions extends ListRecords
{
    protected static string $resource = ThemeRevisionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
