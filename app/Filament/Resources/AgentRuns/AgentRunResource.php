<?php

namespace App\Filament\Resources\AgentRuns;

use App\Filament\Resources\AgentRuns\Pages\CreateAgentRun;
use App\Filament\Resources\AgentRuns\Pages\EditAgentRun;
use App\Filament\Resources\AgentRuns\Pages\ListAgentRuns;
use App\Filament\Resources\AgentRuns\Pages\ViewAgentRun;
use App\Filament\Resources\AgentRuns\Schemas\AgentRunForm;
use App\Filament\Resources\AgentRuns\Schemas\AgentRunInfolist;
use App\Filament\Resources\AgentRuns\Tables\AgentRunsTable;
use App\Models\AgentRun;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AgentRunResource extends Resource
{
    protected static ?string $model = AgentRun::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AgentRunForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AgentRunInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentRunsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\AgentRuns\RelationManagers\AgentStepsRelationManager::class,
            \App\Filament\Resources\AgentRuns\RelationManagers\MediaAssetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgentRuns::route('/'),
            'create' => CreateAgentRun::route('/create'),
            'view' => ViewAgentRun::route('/{record}'),
            'edit' => EditAgentRun::route('/{record}/edit'),
        ];
    }
}
