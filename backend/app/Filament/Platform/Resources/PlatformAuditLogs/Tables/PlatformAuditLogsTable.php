<?php

namespace App\Filament\Platform\Resources\PlatformAuditLogs\Tables;

use App\Models\PlatformAuditLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlatformAuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('platformUser.name')
                    ->label('Operatore')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Azione')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label('Entità')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
                TextColumn::make('ip_address')
                    ->label('Indirizzo IP')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Azione')
                    ->options(fn () => PlatformAuditLog::query()
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action', 'action')
                        ->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
