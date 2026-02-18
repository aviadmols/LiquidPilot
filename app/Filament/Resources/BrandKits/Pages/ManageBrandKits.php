<?php

namespace App\Filament\Resources\BrandKits\Pages;

use App\Filament\Resources\BrandKits\BrandKitResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Table;

class ManageBrandKits extends ManageRecords
{
    protected static string $resource = BrandKitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateFormDataUsing(fn (array $data): array => $this->mutateBrandKitFormData($data)),
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(fn (array $data): array => $this->mutateBrandKitFormData($data)),
                DeleteAction::make(),
            ]);
    }

    private function mutateBrandKitFormData(array $data): array
    {
        $data['colors_json'] = [
            'primary' => $data['colors_primary'] ?? null,
            'secondary' => $data['colors_secondary'] ?? null,
            'background' => $data['colors_background'] ?? null,
        ];
        unset($data['colors_primary'], $data['colors_secondary'], $data['colors_background']);

        $data['typography_json'] = array_filter([
            'heading_font' => $data['typography_heading_font'] ?? null,
            'body_font' => $data['typography_body_font'] ?? null,
            'notes' => $data['typography_notes'] ?? null,
        ], fn ($v) => $v !== null && $v !== '');
        unset($data['typography_heading_font'], $data['typography_body_font'], $data['typography_notes']);

        $keywords = isset($data['imagery_keywords']) && is_string($data['imagery_keywords'])
            ? array_map('trim', explode(',', $data['imagery_keywords']))
            : [];
        $data['imagery_style_json'] = [
            'keywords' => array_filter($keywords),
            'vibe' => $data['imagery_vibe'] ?? null,
        ];
        unset($data['imagery_keywords'], $data['imagery_vibe']);

        return $data;
    }
}
