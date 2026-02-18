<?php

namespace App\Filament\Resources\AgentRuns\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AgentRunInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextEntry::make('id'),
                    TextEntry::make('project.name'),
                    TextEntry::make('themeRevision.original_filename')->label('Theme'),
                    TextEntry::make('mode'),
                    TextEntry::make('output_format')->label('Output'),
                    TextEntry::make('status'),
                    TextEntry::make('progress')->suffix('%'),
                    TextEntry::make('started_at')->dateTime(),
                    TextEntry::make('finished_at')->dateTime(),
                    TextEntry::make('error')->columnSpanFull(),
                ])->columns(2),
            ]);
    }
}
