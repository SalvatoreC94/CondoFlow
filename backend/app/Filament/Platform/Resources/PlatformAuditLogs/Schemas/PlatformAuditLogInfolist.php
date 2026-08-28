<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PlatformAuditLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Evento')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('action')->label('Azione'),
                        TextEntry::make('platformUser.name')->label('Operatore')->placeholder('—'),
                        TextEntry::make('auditable_type')->label('Entità')->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                        TextEntry::make('ip_address')->label('Indirizzo IP')->placeholder('—'),
                        TextEntry::make('created_at')->label('Data')->dateTime('d/m/Y H:i:s'),
                    ]),
                Section::make('Dettagli')
                    ->columns(2)
                    ->schema([
                        KeyValueEntry::make('old_values')->label('Valori precedenti'),
                        KeyValueEntry::make('new_values')->label('Nuovi valori'),
                    ]),
            ]);
    }
}
