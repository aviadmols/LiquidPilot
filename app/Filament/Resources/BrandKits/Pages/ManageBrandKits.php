<?php

namespace App\Filament\Resources\BrandKits\Pages;

use App\Filament\Resources\BrandKits\BrandKitResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBrandKits extends ManageRecords
{
    protected static string $resource = BrandKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
