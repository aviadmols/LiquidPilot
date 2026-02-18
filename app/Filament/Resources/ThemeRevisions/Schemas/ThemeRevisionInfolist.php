<?php

namespace App\Filament\Resources\ThemeRevisions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ThemeRevisionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()->schema([
                    TextEntry::make('original_filename')->label('File'),
                    TextEntry::make('status'),
                    TextEntry::make('signature_sha256')->label('Signature')->limit(16),
                    TextEntry::make('scanned_at')->label('Scanned at')->dateTime(),
                    TextEntry::make('error')->columnSpanFull()->visible(fn ($record) => $record && filled($record->error)),
                ])->columns(2),
            ]);
    }
}
