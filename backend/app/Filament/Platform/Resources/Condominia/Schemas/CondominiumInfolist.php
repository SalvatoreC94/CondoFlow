<?php

namespace App\Filament\Platform\Resources\Condominia\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CondominiumInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Condominio')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Nome'),
                        TextEntry::make('administrator.name')->label('Amministratore'),
                        TextEntry::make('address')->label('Indirizzo'),
                        TextEntry::make('city')->label('Città'),
                        TextEntry::make('province')->label('Provincia'),
                        TextEntry::make('postal_code')->label('CAP'),
                        TextEntry::make('total_units')->label('Unità totali'),
                        TextEntry::make('buildings_count')->label('Edifici')->state(fn ($record) => $record->buildings()->count()),
                        TextEntry::make('units_count')->label('Unità censite')->state(fn ($record) => $record->units()->count()),
                        TextEntry::make('created_at')->label('Creato il')->dateTime('d/m/Y H:i'),
                    ]),
            ]);
    }
}
