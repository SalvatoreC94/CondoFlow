<?php

namespace App\Filament\Platform\Resources\AuditLogs\Tables;

use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogsTable
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
                TextColumn::make('action')
                    ->label('Azione')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Utente')
                    ->placeholder('Sistema')
                    ->searchable(),
                TextColumn::make('condominium.name')
                    ->label('Condominio')
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label('Entità')
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—'),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Azione')
                    ->options(fn () => AuditLog::query()
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
