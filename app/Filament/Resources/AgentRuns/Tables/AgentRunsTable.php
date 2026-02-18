<?php

namespace App\Filament\Resources\AgentRuns\Tables;

use App\Models\AgentRun;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AgentRunsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('project.name'),
                TextColumn::make('themeRevision.original_filename')->label('Theme'),
                TextColumn::make('mode'),
                TextColumn::make('status')->badge(),
                TextColumn::make('progress')->suffix('%'),
                TextColumn::make('started_at')->dateTime(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
